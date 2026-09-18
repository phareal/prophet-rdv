<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; background: #f8fafc; }
    .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: #1D9E75; padding: 24px 32px; }
    .header h1 { color: white; font-size: 20px; margin: 0; }
    .body { padding: 32px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
    td:first-child { color: #64748b; font-size: 13px; width: 40%; }
    td:last-child { color: #0F172A; font-size: 14px; font-weight: 500; }
    .message-box { background: #f8fafc; border-radius: 8px; padding: 16px; color: #475569; margin-top: 16px; white-space: pre-wrap; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header"><h1>🔔 Nouvelle demande de RDV</h1></div>
    <div class="body">
      <table>
        <tr><td>Nom complet</td><td>{{ $rdv['prenom'] }} {{ $rdv['nom'] }}</td></tr>
        <tr><td>Email</td><td>{{ $rdv['email'] }}</td></tr>
        <tr><td>Téléphone</td><td>{{ $rdv['telephone'] }}</td></tr>
        <tr><td>Pays</td><td>{{ $rdv['pays'] }}</td></tr>
        <tr><td>Type de consultation</td><td>{{ $rdv['type_consultation'] }}</td></tr>
        <tr><td>Date souhaitée</td><td>{{ $dateFr }}</td></tr>
        <tr><td>Heure</td><td>{{ $rdv['heure'] }}</td></tr>
        <tr><td>Mode de paiement</td><td>{{ $modePaiementLabel }}</td></tr>
      </table>
      @if ($rdv['message'])
        <div class="message-box"><strong>Message :</strong>
{{ $rdv['message'] }}</div>
      @endif
    </div>
  </div>
</body>
</html>
