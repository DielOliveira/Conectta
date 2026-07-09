<style>
    .ct-app-version {
        color: #94a3b8;
        font-size: 12px;
        font-weight: 700;
        padding: 0 24px 18px;
        text-align: right;
    }

    @media (max-width: 700px) {
        .ct-app-version {
            padding-inline: 16px;
            text-align: center;
        }
    }
</style>

<div class="ct-app-version">
    Sistema {{ \App\Support\AppVersion::label() }}
</div>
