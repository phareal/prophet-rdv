{{--
  Template Name: Don — Merci

  Page de retour du don (tâche 9), adressée par un jeton opaque (`ref`)
  résolu côté serveur (voir App\View\Composers\DonMerci) — même principe que
  template-confirmation.blade.php pour le rendez-vous. Un jeton absent ou
  inconnu affiche un état « introuvable » poli, jamais une erreur.

  Trois états de paiement, tous résolus côté serveur : `paye` (reçu),
  `echoue`/`annule` (réessayer), et `en_attente`/`non_requis` (paiement pas
  encore abouti — ce dernier couvre aussi le cas où l'initialisation Moneroo
  a échoué avant même d'écrire un statut : voir $erreurPaiement).

  Layout minimal, comme template-confirmation.blade.php : pas de navbar, pas
  de bouton WhatsApp flottant.
--}}

@extends('layouts.minimal')

@section('content')
  <div class="don-page">
    <div class="sec-don don-merci">
      @if ($introuvable)
        <div class="don-merci__card">
          <div class="don-merci__head don-merci__head--erreur">
            <div class="don-merci__ring don-merci__ring--erreur">
              <x-icon name="x" :size="26" class="don-merci__icon don-merci__icon--erreur" />
            </div>
            <div>
              <span class="don-merci__eyebrow don-merci__eyebrow--erreur">Don introuvable</span>
              <h1 class="don-merci__title">Lien invalide</h1>
              <p class="don-merci__sub">Ce lien n'est plus valable.</p>
            </div>
          </div>

          <div class="don-merci__body">
            <div class="don-merci__info">
              <p>Le lien que vous avez suivi ne correspond à aucun don connu, ou n'est plus valable.</p>
            </div>

            <div class="don-merci__actions">
              <a href="{{ home_url('/don/') }}" class="don-merci__btn don-merci__btn--outline">
                <x-icon name="home" :size="16" />
                <span>Faire un don</span>
              </a>
            </div>
          </div>
        </div>
      @else
        {{-- Comparaisons sur des chaînes littérales, comme
             template-confirmation.blade.php ($paiement['statut'] === 'paye') :
             pas d'import de ProphetCore\Paiement\Statut dans les vues. --}}
        @php
          $recu = $paiement['statut'] === 'paye';
          $echoue = in_array($paiement['statut'], ['echoue', 'annule'], true);
        @endphp

        <div class="don-merci__card">
          <div class="don-merci__head @if (! $recu) don-merci__head--{{ $echoue ? 'erreur' : 'attente' }} @endif">
            <div class="don-merci__ring @if (! $recu) don-merci__ring--{{ $echoue ? 'erreur' : 'attente' }} @endif">
              <x-icon :name="$recu ? 'check-circle-2' : ($echoue ? 'x' : 'clock')" :size="26"
                      class="don-merci__icon @if (! $recu) don-merci__icon--{{ $echoue ? 'erreur' : 'attente' }} @endif" />
            </div>
            <div>
              <span class="don-merci__eyebrow @if (! $recu) don-merci__eyebrow--{{ $echoue ? 'erreur' : 'attente' }} @endif">
                {{ $recu ? 'Don reçu' : ($echoue ? 'Paiement échoué' : 'En attente de paiement') }}
              </span>
              <h1 class="don-merci__title">{{ $recu ? 'Merci pour votre don' : 'Presque terminé' }}</h1>
              <p class="don-merci__sub">
                {{ $recu ? 'Que Dieu vous bénisse abondamment.' : 'Votre don est enregistré, il ne manque que le règlement.' }}
              </p>
            </div>
          </div>

          <div class="don-merci__body">
            @if ($erreurPaiement)
              <p class="don-merci__erreur" role="alert">
                Le paiement n'a pas pu être lancé. Votre don est bien enregistré — vous pouvez réessayer ci-dessous.
              </p>
            @endif

            <div class="don-merci__summary">
              <p class="don-merci__row">
                <span class="don-merci__dot"></span>
                <span>{{ $don['motif_titre'] }}</span>
              </p>
              <p class="don-merci__row">
                <strong>{{ $don['montant'] }} {{ $don['devise'] }}</strong>
              </p>
              @if ($don['prenom'] || $don['nom'])
                <p class="don-merci__row">
                  <x-icon name="user" :size="13" />
                  <span>{{ $don['prenom'] }} {{ $don['nom'] }}</span>
                </p>
              @endif
            </div>

            @if ($recu)
              <div class="don-merci__info">
                <p>Un reçu vous a été envoyé par email.</p>
              </div>
            @else
              <form method="get" action="{{ esc_url(admin_url('admin-post.php')) }}" class="don-merci__actions">
                <input type="hidden" name="action" value="{{ $actionPaiement }}">
                <input type="hidden" name="ref" value="{{ $don['ref'] }}">
                <button type="submit" class="don-merci__btn don-merci__btn--emerald">
                  {{ $echoue ? 'Réessayer le paiement' : 'Procéder au paiement' }}
                </button>
              </form>
            @endif

            <div class="don-merci__actions">
              <a href="{{ home_url('/don/') }}" class="don-merci__btn don-merci__btn--outline">
                <x-icon name="heart" :size="16" />
                <span>Faire un autre don</span>
              </a>
              <a href="{{ home_url('/') }}" class="don-merci__btn don-merci__btn--outline">
                <x-icon name="home" :size="16" />
                <span>Retour à l'accueil</span>
              </a>
            </div>
          </div>
        </div>
      @endif

      <p class="don-merci__footer">
        © {{ date('Y') }} Prophète Jeremiah Nahoum · Le Conseiller des Rois
      </p>
    </div>
  </div>
@endsection
