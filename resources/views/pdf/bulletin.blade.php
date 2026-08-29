<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bulletin — {{ $eleve->nom }} {{ $eleve->prenom }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        .entete { width: 100%; margin-bottom: 16px; }
        .entete td { vertical-align: top; }
        .entete .logo { width: 70px; }
        .entete .logo img { width: 60px; }
        .etablissement-nom { font-size: 15px; font-weight: bold; }
        .etablissement-info { font-size: 9px; color: #444; }
        h1 { text-align: center; font-size: 15px; margin: 10px 0; text-transform: uppercase; }
        .infos-eleve { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .infos-eleve td { padding: 3px 6px; }
        table.notes { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.notes th, table.notes td { border: 1px solid #999; padding: 5px 8px; }
        table.notes th { background-color: #eee; text-align: left; }
        table.notes td.centre, table.notes th.centre { text-align: center; }
        .synthese { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .synthese td { padding: 4px 8px; }
        .synthese .label { font-weight: bold; width: 40%; }
        .mention { font-size: 13px; font-weight: bold; text-align: center; margin: 14px 0; }
        .appreciation { margin-top: 10px; border-top: 1px solid #999; padding-top: 8px; }
        .pied { margin-top: 30px; font-size: 9px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <table class="entete">
        <tr>
            @if($etablissement?->logo_url)
                <td class="logo"><img src="{{ $etablissement->logo_url }}" alt=""></td>
            @endif
            <td>
                <div class="etablissement-nom">{{ $etablissement->nom ?? "Établissement" }}</div>
                <div class="etablissement-info">
                    {{ $etablissement->adresse ?? '' }}
                    @if($etablissement?->telephone) — {{ $etablissement->telephone }} @endif
                </div>
            </td>
        </tr>
    </table>

    <h1>Bulletin de notes — {{ $trimestre->numero == 1 ? '1ᵉʳ' : $trimestre->numero.'ᵉ' }} trimestre</h1>

    <table class="infos-eleve">
        <tr>
            <td><strong>Élève :</strong> {{ $eleve->nom }} {{ $eleve->prenom }}</td>
            <td><strong>Matricule :</strong> {{ $eleve->matricule ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Classe :</strong> {{ $classe->libelle ?? '—' }}</td>
            <td><strong>Année scolaire :</strong> {{ $trimestre->anneeScolaire?->libelle ?? '—' }}</td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            <tr>
                <th>Matière</th>
                <th class="centre">Coefficient</th>
                <th class="centre">Moyenne</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lignesMatieres as $ligne)
                <tr>
                    <td>{{ $ligne['matiere']->libelle }}</td>
                    <td class="centre">{{ $ligne['matiere']->coefficient }}</td>
                    <td class="centre">{{ $ligne['moyenne'] !== null ? number_format($ligne['moyenne'], 2) : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="centre">Aucune note saisie pour ce trimestre.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="synthese">
        <tr>
            <td class="label">Moyenne générale</td>
            <td>{{ $bulletin->moyenne_generale !== null ? number_format((float) $bulletin->moyenne_generale, 2) : '—' }} / 20</td>
        </tr>
        <tr>
            <td class="label">Rang</td>
            <td>{{ $bulletin->rang ?? '—' }}{{ $bulletin->effectif_classe ? ' / '.$bulletin->effectif_classe : '' }}</td>
        </tr>
        <tr>
            <td class="label">Absences justifiées</td>
            <td>{{ $bulletin->nb_absences_justifiees }}</td>
        </tr>
        <tr>
            <td class="label">Absences non justifiées</td>
            <td>{{ $bulletin->nb_absences_non_justifiees }}</td>
        </tr>
    </table>

    @if($bulletin->mention)
        <div class="mention">{{ $bulletin->mention }}</div>
    @endif

    @if($bulletin->appreciation_dir)
        <div class="appreciation">
            <strong>Appréciation du directeur :</strong>
            <p>{{ $bulletin->appreciation_dir }}</p>
        </div>
    @endif

    <div class="pied">Document généré automatiquement le {{ ($bulletin->genere_at ?? now())->format('d/m/Y à H:i') }}.</div>
</body>
</html>
