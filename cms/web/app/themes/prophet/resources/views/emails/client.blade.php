<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'Inter', Arial, sans-serif; background: #f8fafc; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: #0F172A; padding: 40px 32px; text-align: center; }
    .header h1 { color: #1D9E75; font-size: 22px; margin: 0 0 4px; letter-spacing: 0.5px; }
    .header p { color: #94a3b8; font-size: 14px; margin: 0; }
    .gold-bar { height: 3px; background: linear-gradient(90deg, #BA7517, #f0a829, #BA7517); }
    .body { padding: 32px; }
    .greeting { font-size: 18px; color: #0F172A; font-weight: 600; margin-bottom: 8px; }
    .text { color: #475569; line-height: 1.7; margin-bottom: 24px; }
    .card { background: #E1F5EE; border-left: 4px solid #1D9E75; border-radius: 8px; padding: 20px 24px; margin: 24px 0; }
    .card-row { display: flex; margin-bottom: 10px; }
    .card-label { color: #64748b; font-size: 13px; width: 160px; flex-shrink: 0; }
    .card-value { color: #0F172A; font-size: 14px; font-weight: 500; }
    .badge { display: inline-block; background: #1D9E75; color: white; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 500; }
    .footer { background: #0F172A; padding: 24px 32px; text-align: center; }
    .footer p { color: #64748b; font-size: 12px; margin: 4px 0; }
    .footer a { color: #1D9E75; text-decoration: none; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>✝ Prophète Jeremiah Nahoum</h1>
      <p>Le Conseiller des Rois</p>
    </div>
    <div class="gold-bar"></div>
    <div class="body">
      <div class="greeting">Bonjour {{ $rdv['prenom'] }} {{ $rdv['nom'] }},</div>
      <p class="text">
        Votre demande de rendez-vous a bien été reçue. Nous vous contacterons très prochainement pour confirmer votre consultation.
      </p>
      <div class="card">
        <div class="card-row"><span class="card-label">🔮 Consultation</span><span class="card-value"><span class="badge">{{ $rdv['type_consultation'] }}</span></span></div>
        <div class="card-row"><span class="card-label">📅 Date souhaitée</span><span class="card-value">{{ $dateFr }}</span></div>
        <div class="card-row"><span class="card-label">⏰ Heure</span><span class="card-value">{{ $rdv['heure'] }}</span></div>
        <div class="card-row"><span class="card-label">🌍 Pays</span><span class="card-value">{{ $rdv['pays'] }}</span></div>
        <div class="card-row"><span class="card-label">💳 Paiement</span><span class="card-value">{{ $modePaiementLabel }}</span></div>
        <div class="card-row"><span class="card-label">📞 Téléphone</span><span class="card-value">{{ $rdv['telephone'] }}</span></div>
      </div>
      <p class="text">
        Pour toute question urgente, vous pouvez contacter directement le Prophète via WhatsApp :
        <br><strong style="color:#1D9E75">{{ $phone1 }}</strong>
        @if ($phone2)
          &nbsp;|&nbsp;<strong style="color:#1D9E75">{{ $phone2 }}</strong>
        @endif
      </p>
      <p class="text" style="font-style: italic; color: #BA7517;">
        "Car je connais les projets que j'ai formés sur vous, dit l'Éternel, projets de paix et non de malheur, afin de vous donner un avenir et de l'espérance." — Jér. 29:11
      </p>
    </div>
    <div class="footer">
      <p>© {{ $annee }} Prophète Jeremiah Nahoum — Le Conseiller des Rois</p>
      <p>Ce message a été envoyé automatiquement suite à votre demande sur <a href="{{ $siteUrl }}">{{ $siteUrl }}</a></p>
    </div>
  </div>
</body>
</html>
