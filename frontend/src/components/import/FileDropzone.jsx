import { useRef, useState } from 'react';
import { isXlsxFile } from '../../utils/validators';
import { formatFileSize } from '../../utils/formatters';

export default function FileDropzone({ file, onSelect, error }) {
  const inputRef = useRef(null);
  const [isDragOver, setIsDragOver] = useState(false);

  function handleFiles(fileList) {
    const selected = fileList?.[0];
    if (!selected) return;

    if (!isXlsxFile(selected)) {
      onSelect(null, 'Only .xlsx files are supported.');
      return;
    }

    onSelect(selected, null);
  }

  return (
    <div>
      <div
        onClick={() => inputRef.current?.click()}
        onDragOver={(e) => {
          e.preventDefault();
          setIsDragOver(true);
        }}
        onDragLeave={() => setIsDragOver(false)}
        onDrop={(e) => {
          e.preventDefault();
          setIsDragOver(false);
          handleFiles(e.dataTransfer.files);
        }}
        style={{
          border: `2px dashed ${isDragOver ? 'var(--color-verdigris)' : 'var(--color-paper-line)'}`,
          borderRadius: 'var(--radius-lg)',
          padding: '40px 24px',
          textAlign: 'center',
          cursor: 'pointer',
          background: isDragOver ? 'var(--color-paper-dim)' : 'var(--color-surface)',
          transition: 'border-color 150ms ease, background 150ms ease',
        }}
      >
        <input
          ref={inputRef}
          type="file"
          accept=".xlsx"
          hidden
          onChange={(e) => handleFiles(e.target.files)}
        />
        {file ? (
          <>
            <div style={{ fontWeight: 600 }}>{file.name}</div>
            <div className="mono" style={{ fontSize: 'var(--fs-xs)', color: 'var(--color-ink-soft)', marginTop: 4 }}>
              {formatFileSize(file.size)} · click or drop to replace
            </div>
          </>
        ) : (
          <>
            <div style={{ fontWeight: 600, marginBottom: 4 }}>Drop an .xlsx file here, or click to browse</div>
            <div style={{ fontSize: 'var(--fs-xs)', color: 'var(--color-ink-soft)' }}>
              Each row becomes an archive record; embedded images are linked automatically.
            </div>
          </>
        )}
      </div>
      {error && <p className="field-error" style={{ marginTop: 8 }}>{error}</p>}
    </div>
  );
}