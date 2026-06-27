@once
    @push('styles')
    <style>
        .prms-interface-preview {
            position: relative;
            overflow: hidden;
            border-radius: 0.75rem;
            border: 1px solid var(--prms-border-soft, #e2e8f0);
            background: #fff;
        }

        .prms-interface-preview--sm {
            width: 132px;
            height: 100px;
            min-width: 132px;
        }

        .prms-interface-preview--md {
            width: 240px;
            height: 180px;
            min-width: 240px;
            flex-shrink: 0;
        }

        .prms-interface-preview--lg {
            width: 100%;
            max-height: 560px;
            aspect-ratio: 16 / 10;
        }

        .prms-interface-preview-image,
        .prms-interface-preview-placeholder {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .prms-interface-preview-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--prms-surface-soft, #f1f5f9);
            color: var(--prms-primary, #1572E8);
            font-size: 1.35rem;
        }

        .prms-interface-preview-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(
                180deg,
                rgba(15, 23, 42, 0.28) 0%,
                rgba(15, 23, 42, 0.58) 100%
            );
            transition: background 0.35s ease;
        }

        .prms-interface-preview:hover .prms-interface-preview-overlay,
        .prms-interface-preview:focus-within .prms-interface-preview-overlay {
            background: linear-gradient(
                180deg,
                rgba(15, 23, 42, 0.42) 0%,
                rgba(15, 23, 42, 0.78) 100%
            );
        }

        .prms-interface-preview-default {
            display: none;
        }

        .prms-interface-preview-actions {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.35rem 0.45rem;
            padding: 0.35rem;
        }

        .prms-interface-preview--sm .prms-interface-preview-actions {
            gap: 0.2rem 0.3rem;
            padding: 0.25rem;
        }

        .prms-interface-preview--md .prms-interface-preview-actions {
            gap: 0.35rem 0.5rem;
            padding: 0.5rem;
        }

        .prms-interface-preview--lg .prms-interface-preview-actions {
            gap: 0.65rem 0.85rem;
            padding: 1rem;
        }

        .prms-interface-preview-action {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            min-width: 2.5rem;
            padding: 0;
            border: 0;
            background: transparent;
            color: #fff;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }

        .prms-interface-preview--sm .prms-interface-preview-action {
            min-width: 2.1rem;
            gap: 0.15rem;
        }

        .prms-interface-preview--md .prms-interface-preview-action {
            min-width: 2.75rem;
            gap: 0.25rem;
        }

        .prms-interface-preview--lg .prms-interface-preview-action {
            min-width: 4.75rem;
            gap: 0.45rem;
        }

        .prms-interface-preview-action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 999px;
            color: #fff;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.28);
            transition: transform 0.28s cubic-bezier(0.34, 1.4, 0.64, 1),
                        box-shadow 0.28s ease,
                        filter 0.28s ease;
        }

        .prms-interface-preview--sm .prms-interface-preview-action-icon {
            width: 2.05rem;
            height: 2.05rem;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.24);
        }

        .prms-interface-preview--md .prms-interface-preview-action-icon {
            width: 2.85rem;
            height: 2.85rem;
            box-shadow: 0 5px 16px rgba(15, 23, 42, 0.28);
        }

        .prms-interface-preview--lg .prms-interface-preview-action-icon {
            width: 4.25rem;
            height: 4.25rem;
            font-size: 1.55rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.32);
        }

        .prms-interface-preview-action i {
            font-size: 0.95rem;
            line-height: 1;
        }

        .prms-interface-preview--sm .prms-interface-preview-action i {
            font-size: 0.82rem;
        }

        .prms-interface-preview--md .prms-interface-preview-action i {
            font-size: 1.05rem;
        }

        .prms-interface-preview--lg .prms-interface-preview-action i {
            font-size: 1.55rem;
        }

        .prms-interface-preview-action--preview .prms-interface-preview-action-icon {
            background: rgba(21, 114, 232, 0.88);
            animation: prms-action-glow-blue 2.8s ease-in-out infinite;
        }

        .prms-interface-preview-action--demo .prms-interface-preview-action-icon {
            background: rgba(13, 148, 136, 0.9);
            animation: prms-action-glow-teal 2.8s ease-in-out infinite 0.35s;
        }

        .prms-interface-preview-action--video .prms-interface-preview-action-icon {
            background: rgba(220, 38, 38, 0.88);
            animation: prms-action-glow-red 2.8s ease-in-out infinite 0.7s;
        }

        .prms-interface-preview-action--download .prms-interface-preview-action-icon {
            background: rgba(217, 119, 6, 0.9);
            animation: prms-action-glow-amber 2.8s ease-in-out infinite 1.05s;
        }

        .prms-interface-preview-action:hover .prms-interface-preview-action-icon,
        .prms-interface-preview-action:focus-visible .prms-interface-preview-action-icon {
            transform: scale(1.12) translateY(-2px);
            filter: brightness(1.08);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.38);
        }

        .prms-interface-preview-action:hover,
        .prms-interface-preview-action:focus-visible {
            color: #fff;
            text-decoration: none;
            outline: none;
        }

        .prms-interface-preview-label {
            display: block;
            font-size: 0.58rem;
            font-weight: 600;
            line-height: 1.15;
            letter-spacing: 0.01em;
            text-shadow: 0 1px 2px rgba(15, 23, 42, 0.65);
            max-width: 4.25rem;
        }

        .prms-interface-preview--sm .prms-interface-preview-label {
            font-size: 0.5rem;
            max-width: 3.1rem;
            line-height: 1.1;
        }

        .prms-interface-preview--md .prms-interface-preview-label {
            font-size: 0.62rem;
            max-width: 4rem;
            line-height: 1.15;
        }

        .prms-interface-preview--lg .prms-interface-preview-label {
            font-size: 0.82rem;
            max-width: 6.5rem;
        }

        @keyframes prms-action-glow-blue {
            0%, 100% { background-color: rgba(21, 114, 232, 0.78); }
            50% { background-color: rgba(59, 130, 246, 0.98); }
        }

        @keyframes prms-action-glow-teal {
            0%, 100% { background-color: rgba(13, 148, 136, 0.78); }
            50% { background-color: rgba(20, 184, 166, 0.98); }
        }

        @keyframes prms-action-glow-red {
            0%, 100% { background-color: rgba(220, 38, 38, 0.78); }
            50% { background-color: rgba(239, 68, 68, 0.98); }
        }

        @keyframes prms-action-glow-amber {
            0%, 100% { background-color: rgba(217, 119, 6, 0.78); }
            50% { background-color: rgba(245, 158, 11, 0.98); }
        }

        @media (prefers-reduced-motion: reduce) {
            .prms-interface-preview-action-icon {
                animation: none !important;
            }

            .prms-interface-preview-action:hover .prms-interface-preview-action-icon,
            .prms-interface-preview-action:focus-visible .prms-interface-preview-action-icon {
                transform: none;
            }
        }
    </style>
    @endpush
@endonce
