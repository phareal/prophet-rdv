{{--
  Template Name: Confirmation

  Port de pages/confirmation.vue. La source Nuxt lisait les champs affichés
  depuis la query string et n'avait donc aucun état « introuvable » — elle
  affichait toujours la carte, avec des lignes vides masquées par v-if quand
  un champ manquait. Ici, la page est adressée par un jeton opaque (`ref`)
  résolu côté serveur (voir Confirmation.php) : un jeton absent ou inconnu ne
  doit ni planter ni afficher une carte à moitié vide, d'où l'état
  `$introuvable` ajouté par le brief, absent de la source. Il reprend
  l'habillage `.conf__card` pour rester visuellement cohérent avec le reste
  de la page.

  Layout minimal (voir layouts/minimal.blade.php) : pages/confirmation.vue:24
  ne montait ni TheNavbar, ni AppFooter, ni FloatingWhatsApp.
--}}

@extends('layouts.minimal')

@section('content')
  <div class="sec-confirmation conf">
    {{-- Fond décoratif léger --}}
    <div class="conf__bg">
      <div class="conf__bg-radial conf__bg-radial--gold"></div>
      <div class="conf__bg-radial conf__bg-radial--green"></div>
    </div>

    {{-- Carte --}}
    <div class="conf__card">
      @if ($introuvable)
        <div class="conf__head">
          <div class="conf__check-ring">
            <x-icon name="x" :size="28" class="conf__check-icon" />
          </div>
          <div class="conf__head-text">
            <span class="conf__head-eyebrow">Demande introuvable</span>
            <h1 class="conf__head-title">Lien <em>invalide</em></h1>
            <p class="conf__head-sub">Ce lien de confirmation n'est plus valable.</p>
          </div>
        </div>

        <div class="conf__body">
          <div class="conf__info">
            <p>Le lien que vous avez suivi ne correspond à aucune demande de rendez-vous connue, ou n'est plus valable.</p>
          </div>

          <div class="conf__actions">
            <a href="{{ home_url('/rdv') }}" class="conf__btn conf__btn--outline">
              <x-icon name="home" :size="16" />
              <span>Reprendre une demande</span>
            </a>
          </div>
        </div>
      @else
        {{-- Header --}}
        <div class="conf__head">
          <div class="conf__check-ring">
            <x-icon name="check-circle-2" :size="28" class="conf__check-icon" />
          </div>
          <div class="conf__head-text">
            <span class="conf__head-eyebrow">Demande reçue</span>
            <h1 class="conf__head-title">Rendez-vous <em>enregistré</em></h1>
            <p class="conf__head-sub">Votre demande a bien été transmise au Prophète.</p>
          </div>
        </div>

        {{-- Corps --}}
        <div class="conf__body">

          {{-- Récapitulatif --}}
          <div class="conf__summary">
            <p class="conf__summary-label">Récapitulatif</p>
            <div class="conf__rows">
              @if ($rdv['prenom'] || $rdv['nom'])
                <div class="conf__row">
                  <x-icon name="user" :size="13" class="conf__row-icon" />
                  <span>{{ $rdv['prenom'] }} {{ $rdv['nom'] }}</span>
                </div>
              @endif
              @if ($rdv['type_consultation'])
                <div class="conf__row">
                  <span class="conf__row-dot"></span>
                  <span>{{ $rdv['type_consultation'] }}</span>
                </div>
              @endif
              @if ($dateFr)
                <div class="conf__row">
                  <x-icon name="calendar-days" :size="13" class="conf__row-icon" />
                  <span>{{ $dateFr }}</span>
                </div>
              @endif
              @if ($rdv['heure'])
                <div class="conf__row">
                  <x-icon name="clock" :size="13" class="conf__row-icon" />
                  <span>{{ $rdv['heure'] }}</span>
                </div>
              @endif
            </div>
          </div>

          {{-- Info --}}
          <div class="conf__info">
            <p>Un email de confirmation vous a été envoyé avec tous les détails de votre rendez-vous.</p>
            <p>Votre demande sera traitée dans les <strong>24–48h</strong>. Le Prophète vous contactera directement.</p>
          </div>

          {{-- Verset --}}
          <blockquote class="conf__verse">
            <span class="conf__verse-cross">✦</span>
            <p>"Invoque-moi, et je te répondrai ; je t'annoncerai de grandes choses, des choses cachées, que tu ne connais pas."</p>
            <cite>— Jérémie 33:3</cite>
          </blockquote>

          {{-- Actions --}}
          <div class="conf__actions">
            @if ($waUrl)
              <a href="{{ esc_url($waUrl) }}" target="_blank" rel="noopener noreferrer" class="conf__btn conf__btn--wa">
                <x-icon name="message-circle" :size="16" />
                <span>Confirmer sur WhatsApp</span>
              </a>
            @endif
            <a href="{{ home_url('/') }}" class="conf__btn conf__btn--outline">
              <x-icon name="home" :size="16" />
              <span>Retour à l'accueil</span>
            </a>
          </div>

        </div>
      @endif
    </div>

    {{-- Footer --}}
    <p class="conf__footer">
      © {{ date('Y') }} Prophète Jeremiah Nahoum · Le Conseiller des Rois
    </p>
  </div>
@endsection
