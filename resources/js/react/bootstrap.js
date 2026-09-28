export function readPageBootstrap() {
    const node = document.getElementById('react-page-bootstrap');

    if (!node) {
        throw new Error('Missing React page bootstrap data.');
    }

    return JSON.parse(node.textContent || '{}');
}
