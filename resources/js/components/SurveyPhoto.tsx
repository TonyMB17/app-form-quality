import {
    Camera,
    ZoomIn,
    ImagePlus,
    Trash2,
    ShieldCheck,
    ScanLine,
    CircleCheck,
    LoaderCircle,
    AlertCircle,
} from "lucide-react";
import { useEffect, useRef, useState } from "react";
import PhotoLightbox from "./PhotoLightbox";
export default function SurveyPhoto({
    value,
    onChange,
    onBusy,
}: {
    value: File | null;
    onChange: (file: File | null) => void;
    onBusy: (busy: boolean) => void;
}) {
    const [expanded, setExpanded] = useState(false);
    const cameraButton = useRef<HTMLButtonElement>(null);
    const [preview, setPreview] = useState("");
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    const camera = useRef<HTMLInputElement>(null),
        gallery = useRef<HTMLInputElement>(null);
    useEffect(() => {
        if (!value) {
            setPreview("");
            return;
        }
        const url = URL.createObjectURL(value);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [value]);
    async function select(file?: File) {
        if (!file) return;
        setError("");
        setBusy(true);
        onBusy(true);
        try {
            if (file.size > 25 * 1024 * 1024)
                throw new Error(
                    "La imagen supera 25 MB. Toma otra foto con menor resolución.",
                );
            const bitmap = await createImageBitmap(file);
            const scale = Math.min(
                1,
                2400 / Math.max(bitmap.width, bitmap.height),
            );
            const canvas = document.createElement("canvas");
            canvas.width = Math.round(bitmap.width * scale);
            canvas.height = Math.round(bitmap.height * scale);
            const ctx = canvas.getContext("2d");
            if (!ctx) throw new Error("No se pudo preparar la foto.");
            ctx.fillStyle = "white";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            bitmap.close();
            let blob: Blob | null = null;
            for (const quality of [0.9, 0.8, 0.7, 0.6]) {
                blob = await new Promise<Blob | null>((resolve) =>
                    canvas.toBlob(resolve, "image/jpeg", quality),
                );
                if (blob && blob.size <= 1536 * 1024) break;
            }
            if (!blob || blob.size > 1536 * 1024)
                throw new Error(
                    "No se pudo reducir la imagen. Selecciona otra foto.",
                );
            onChange(new File([blob], "encuesta.jpg", { type: "image/jpeg" }));
        } catch (e) {
            setError(
                e instanceof Error && !e.message.includes("decode")
                    ? e.message
                    : "Formato no compatible. Usa una foto JPG, PNG o WebP.",
            );
        } finally {
            setBusy(false);
            onBusy(false);
        }
    }
    return (
        <section
            id="foto"
            tabIndex={-1}
            className={`auth-card survey-photo photo-upload ${value ? "has-photo" : ""}`}
            aria-labelledby="photo-heading"
            aria-busy={busy}
        >
            <header className="photo-header">
                <span className="photo-heading-icon">
                    <Camera size={25} aria-hidden="true" />
                </span>
                <div>
                    <span className="eyebrow">RESPALDO DEL REGISTRO</span>
                    <h2 id="photo-heading">
                        Foto de la encuesta · obligatoria
                    </h2>
                </div>
                <span role="status" className={`photo-status ${value ? "is-ready" : ""}`}>
                    {value ? (
                        <CircleCheck size={15} aria-hidden="true" />
                    ) : (
                        <ImagePlus size={15} aria-hidden="true" />
                    )}
                    {value ? "Foto adjunta" : "Pendiente"}
                </span>
            </header>
            <input
                hidden
                ref={camera}
                type="file"
                accept="image/*"
                capture="environment"
                onChange={(e) => {
                    void select(e.target.files?.[0]);
                    e.target.value = "";
                }}
            />
            <input
                hidden
                ref={gallery}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={(e) => {
                    void select(e.target.files?.[0]);
                    e.target.value = "";
                }}
            />
            {preview ? (
                <div className="photo-preview">
                    <button className="photo-preview-open" type="button" onClick={() => setExpanded(true)} aria-label="Ampliar foto para revisar su legibilidad" disabled={busy}>
                    <img
                        src={preview}
                        alt="Vista previa de la encuesta fotografiada"
                    />
                    <span><ZoomIn size={18} aria-hidden="true" /> Ampliar fotografía</span>
                    </button>
                    <div className="photo-preview-caption">
                        <ScanLine size={18} aria-hidden="true" />
                        <span>
                            Comprueba que se vea toda la hoja y que las respuestas sean legibles.
                        </span>
                    </div>
                </div>
            ) : (
                <div className="photo-empty">
                    <span className="photo-empty-icon">
                        <ScanLine
                            size={38}
                            strokeWidth={1.4}
                            aria-hidden="true"
                        />
                    </span>
                    <h3>Fotografía la hoja completa</h3>
                    <p>Colócala sobre una superficie plana, con buena luz y sin sombras. Comprueba que las respuestas se puedan leer.</p>                    
                    <span>JPG, PNG o WebP</span>
                </div>
            )}
            <div className="photo-actions">
                <button
                    ref={cameraButton}
                    className="photo-camera"
                    type="button"
                    disabled={busy}
                    onClick={() => camera.current?.click()}
                >
                    <Camera size={19} aria-hidden="true" />
                    {value ? "Tomar otra foto" : "Tomar foto"}
                </button>
                <button
                    className="photo-gallery"
                    type="button"
                    disabled={busy}
                    onClick={() => gallery.current?.click()}
                >
                    <ImagePlus size={19} aria-hidden="true" />
                    {value ? "Cambiar imagen" : "Seleccionar imagen"}
                </button>
                {value && (
                    <button
                        className="photo-remove"
                        type="button"
                        disabled={busy}
                        onClick={() => { setError(""); onChange(null); cameraButton.current?.focus(); }}
                    >
                        <Trash2 size={17} aria-hidden="true" />
                        Quitar foto
                    </button>
                )}
            </div>
            {busy && (
                <p className="photo-feedback" role="status">
                    <LoaderCircle
                        className="photo-spinner"
                        size={18}
                        aria-hidden="true"
                    />
                    Preparando foto…
                </p>
            )}
            {error && (
                <p role="alert" className="photo-feedback photo-error">
                    <AlertCircle size={18} aria-hidden="true" />
                    {error}
                </p>
            )}
            <div className="photo-privacy">
                <ShieldCheck size={20} aria-hidden="true" />
                <div>
                    <strong>Respaldo privado de la encuesta</strong>
                    <p>
                        Solo las personas autorizadas podrán consultar esta
                        foto.
                    </p>
                </div>
            </div>
            {expanded && preview && <PhotoLightbox src={preview} onClose={() => setExpanded(false)} />}
        </section>
    );
}
