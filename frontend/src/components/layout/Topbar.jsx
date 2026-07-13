import '../../assets/styles/layout.css';

export default function Topbar({ title, actions }) {
  return (
    <header className="topbar">
      <h2 style={{ marginBottom: 0 }}>{title}</h2>
      {actions && <div>{actions}</div>}
    </header>
  );
}