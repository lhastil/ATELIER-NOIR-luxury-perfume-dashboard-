/**
 * Shows the chosen image before the form is saved.
 *
 * Markup contract: a file input with data-image-input="<img id>". The preview
 * image may start hidden; a sibling .image-field__placeholder is hidden once a
 * preview is shown, and the element with id "<img id>-caption" is updated.
 */
export function initImagePreviews(root = document) {
    root.querySelectorAll('[data-image-input]').forEach((input) => {
        const preview = document.getElementById(input.dataset.imageInput);

        if (!preview) {
            return;
        }

        const placeholder = preview.parentElement.querySelector('.image-field__placeholder');
        const caption = document.getElementById(`${preview.id}-caption`);
        let objectUrl = null;

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file || !file.type.startsWith('image/')) {
                return;
            }

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }

            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
            preview.hidden = false;

            if (placeholder) {
                placeholder.hidden = true;
            }

            if (caption) {
                caption.textContent = 'New image — saved when you submit the form';
            }
        });
    });
}
