# Sitem — Production Architecture at Scale

*Phase 11 of the DevOps/SRE modernization journey. This document assumes a real
multi-node cluster (AWS EKS / GKE / Azure AKS) and thousands-to-millions of
users, building directly on everything implemented in Phases 1–10.*

---

## 1. High Availability

**What you already have:** every Deployment already declares `replicas`,
readiness/liveness probes exist for `backend` and `frontend`, and HPA
(Phase 5) can scale `backend`/`frontend` up to 5 replicas under load.

**What changes at real scale:**
- **Multi-node spread.** On a single-node Minikube, "3 replicas" all live on
  one machine — if that machine dies, everything dies together. On a real
  cluster, add `podAntiAffinity` so the scheduler spreads replicas across
  different Nodes (and ideally different Availability Zones):
  ```yaml
  affinity:
    podAntiAffinity:
      preferredDuringSchedulingIgnoredDuringExecution:
        - weight: 100
          podAffinityTerm:
            labelSelector:
              matchLabels: { app: backend }
            topologyKey: kubernetes.io/hostname
  ```
- **MariaDB becomes the real availability bottleneck.** A single MariaDB
  Pod (even with `Recreate` strategy, per our Phase 7 incident) is a single
  point of failure — losing that one Pod's Node means real downtime until
  it reschedules. Real production needs either a managed database service
  (AWS RDS Multi-AZ, Cloud SQL) or a proper MariaDB Galera/replication
  cluster — a fundamentally different, more complex primitive than the
  single-Pod Deployment we built for local dev.
- **Multi-node cluster itself.** `minikube start` is one control-plane, one
  worker. Real clusters run 3+ control-plane nodes (for etcd quorum) and
  worker nodes spread across zones, managed by the cloud provider.

## 2. Scalability

**What you already have:** HPA (Phase 5) reacting to CPU utilization,
verified with real metrics during this session.

**What changes at real scale:**
- **Scale on more than CPU.** Real traffic spikes often show up as request
  latency or queue depth before CPU visibly climbs. Production HPAs
  frequently scale on custom Prometheus metrics (requests/sec, p99 latency)
  via the `prometheus-adapter`, not just CPU.
- **Cluster Autoscaler.** HPA adds *Pods* — but if there's no Node capacity
  left to schedule them onto, they sit `Pending`. Real clusters pair HPA
  with a Cluster Autoscaler that adds/removes *Nodes* automatically based
  on unschedulable Pods.
- **Database connection limits.** Scaling `backend` from 1 to 20 replicas
  means up to 20× the database connections. This is a real, common failure
  mode — a connection pooler (ProxySQL, PgBouncer-equivalent) sitting in
  front of the database becomes necessary well before 20 replicas.

## 3. Load Balancing

**What you already have:** Kubernetes Services (ClusterIP) + Ingress-nginx,
routing all external traffic through one entry point.

**What changes at real scale:**
- Cloud Ingress controllers integrate with a real cloud Load Balancer (AWS
  ALB, GCP Cloud Load Balancer) — Minikube's `minikube tunnel`/port-forward
  approach is purely a local development stand-in for this.
- **Global load balancing** (routing users to the nearest region) requires
  DNS-based or Anycast routing sitting above any single cluster's Ingress —
  see Multi-Region below.

## 4. Disaster Recovery

**This is the area with the biggest honest gap in what we built.** Today's
real incident (Phase 7) recovered successfully because we caught the PV
`Retain` policy change in time — that was **skill and speed**, not a real
backup strategy. Production needs both.

- **Automated database backups.** A scheduled `CronJob` running `mysqldump`
  (or better, the cloud provider's native snapshot mechanism) to
  object storage on a real schedule (hourly/daily), with a tested restore
  procedure — "tested" meaning someone has actually run the restore before
  needing it in a real emergency, not just written the backup script.
- **Velero** (or the cloud equivalent) for full cluster-state backups —
  PVC snapshots plus Kubernetes object manifests, restorable to a different
  cluster entirely if needed.
- **A real RTO/RPO target**, written down: how much data loss is acceptable
  (Recovery Point Objective) and how long can the system be down (Recovery
  Time Objective)? Today's incident had an RPO of effectively zero (no data
  lost) purely because we acted fast — a written DR plan removes that
  dependency on improvisation under pressure.

## 5. Multi-Region

Not implemented here (would require real cloud infrastructure), but the
architecture, conceptually:
```
        DNS (latency-based routing)
              /            \
     Region: EU-West    Region: US-East
     (full k8s cluster)  (full k8s cluster)
              \            /
        Database replication
        (active-passive, or
         active-active with
         conflict resolution)
```
**Active-passive** (one region serves writes, the other is a warm standby)
is simpler and matches most real companies' actual needs. **Active-active**
(both regions serve writes) is significantly more complex — it requires the
application layer to handle eventual consistency and write conflicts, which
is a real architectural change, not just an infrastructure one.

## 6. Cost Optimization

**What you already have:** resource `requests`/`limits` on every container
(Phase 5) — the actual foundation cost optimization builds on, since cloud
billing is driven by requested capacity.

**What changes at real scale:**
- **Right-sizing** — our `requests`/`limits` values were reasonable
  defaults, never tuned against real production load. Tools like the
  Vertical Pod Autoscaler (in recommendation-only mode) show what
  containers *actually* use versus what's requested — commonly reveals
  significant over-provisioning.
- **Spot/Preemptible Nodes** for stateless, interruption-tolerant workloads
  (`frontend`, `backend`) at a fraction of on-demand cost — never for
  `mariadb`, which needs stable, persistent Nodes.
- **Scale-to-zero** for genuinely idle environments (like `sitem-demo`) —
  real cost savings for non-production environments outside business hours.

## 7. Observability

**Fully implemented in Phase 8** — Prometheus, Grafana, Loki, Alertmanager,
all reused from an existing cluster installation, proven against real
incidents from this very session. At real scale, the main addition would be
**OpenTelemetry distributed tracing** (deliberately deferred in Phase 8 as
requiring actual application code instrumentation) — becomes genuinely
necessary once a single user request touches many more services than our
current four.

## 8. Reliability & SRE Practices

**The single most valuable lesson from this entire session** isn't a tool —
it's what today's real incidents taught firsthand:
- **Readiness/liveness probes** (Phase 5) are what let Kubernetes correctly
  keep old, healthy Pods running while new ones failed to pull images —
  this is *why* your app stayed up during a broken rollout.
- **`atomic` + `cleanup_on_fail`** (added to Terraform after the real
  incident) exist specifically because a tool's *default* failure handling
  is often not safe enough for anything stateful — a lesson learned by
  living through the alternative, not by reading a best-practices list.
- **SLOs/SLIs** — real SRE teams define explicit targets ("99.9% of
  requests succeed in under 500ms") and an **error budget** — a defined
  amount of acceptable failure, which is what actually determines when to
  freeze risky changes (like a Terraform migration) versus when it's safe
  to proceed. Today's Terraform incident, in a real org, would have
  consumed real error budget.
- **A runbook** — a written, specific procedure for "what do you do when X
  happens" — is what turns today's improvised recovery (protecting PVs,
  clearing claim refs, discovering the missing RBAC dependency) into a
  five-minute, low-stress procedure the next time, for anyone on the team,
  not just whoever happened to figure it out live.

## 9. Security (recap, Phases 4 & 10)

Gitleaks, Trivy, Semgrep, SBOM, Cosign signing, SHA-pinned Actions, non-root
containers with minimal capabilities, TLS at the Ingress — all implemented
and verified. At real scale, add: a real certificate authority (cert-manager
+ Let's Encrypt, replacing the self-signed cert), a proper secrets manager
(AWS Secrets Manager/Vault, replacing plain Kubernetes Secrets), and
NetworkPolicy actually enforced (requires a real CNI — Calico/Cilium —
which every major cloud provider's Kubernetes offering supports natively,
unlike our local Minikube default).

## 10. Platform Engineering

The cumulative effect of everything built across Phases 2–10 **is** platform
engineering: a paved path from `git push` to a running, observed, secured,
auto-scaling production system — where an engineer adding a feature never
needs to think about Dockerfiles, Kubernetes YAML, or Terraform state,
because the platform already handles it. That's the actual end goal this
entire journey was building toward.

---

## Closing note

This project started as a PHP/React app running via `php -S` on one Windows
machine. It now has: multi-stage Docker builds, automated CI/CD with real
security scanning and image signing, a genuine Kubernetes deployment
(survived a real data-loss near-miss), Helm packaging, Terraform-managed
infrastructure, a full observability stack, and GitOps automation — plus,
critically, the scar tissue of real incidents actually diagnosed and
recovered, not just described in a tutorial. That difference is the whole
point.