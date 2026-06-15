export async function attachmentToObjectUrl(attachment, token) {
    const res = await fetch(attachment.download_url, {
        headers: { 'Authorization': `Bearer ${token}` },
    });
    if (!res.ok) throw new Error(`Download failed: ${res.status}`);
    const blob = await res.blob();
    return URL.createObjectURL(blob);
}

export function isImageAttachment(attachment) {
    return attachment.file_type?.startsWith('image/');
}