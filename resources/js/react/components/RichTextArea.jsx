import { useEffect, useRef } from 'react';

export default function RichTextArea({ id, name, defaultValue = '', rows = 8 }) {
    const ref = useRef(null);

    useEffect(() => {
        if (!window.tinymce || !ref.current) return undefined;

        window.tinymce.init({
            target: ref.current,
            plugins: 'anchor autolink charmap codesample emoticons link lists media searchreplace table visualblocks wordcount',
            automatic_uploads: true,
            license_key: 'gpl',
            menubar: false,
            height: 260,
        });

        return () => {
            const editor = window.tinymce?.get(id);
            if (editor) editor.remove();
        };
    }, [id]);

    return <textarea ref={ref} id={id} name={name} rows={rows} defaultValue={defaultValue} className="form-control" />;
}
