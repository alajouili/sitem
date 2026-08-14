{{/*
Common labels applied to every resource this chart creates. Defining
this ONCE here means every template below just does
{{ include "sitem.labels" . }} instead of retyping the same block
nine times — and if you ever want to add a new standard label,
you change it in exactly one place.
*/}}
{{- define "sitem.labels" -}}
app.kubernetes.io/name: {{ .Chart.Name }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/version: {{ .Chart.AppVersion }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end -}}