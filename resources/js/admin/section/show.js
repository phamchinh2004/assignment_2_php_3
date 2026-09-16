document.addEventListener('DOMContentLoaded', function () {
    tinymce.init({
        selector: '.sectionContent',
        plugins: 'anchor autolink charmap codesample emoticons link lists media searchreplace table visualblocks wordcount',
        automatic_uploads: true,
        license_key: 'gpl',
        readonly: true,
    });
});
