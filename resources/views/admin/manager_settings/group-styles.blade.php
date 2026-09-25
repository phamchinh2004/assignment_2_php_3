<style>
        .permission-module {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: #fff;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .permission-module__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.1rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
        }
        .permission-module__title {
            display: flex;
            align-items: center;
            gap: .65rem;
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
        }
        .permission-module__body {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
            padding: 1rem;
        }
@media (max-width: 900px) { .permission-module__body { grid-template-columns: 1fr; } }
</style>
