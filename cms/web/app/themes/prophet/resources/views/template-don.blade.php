{{--
  Template Name: Don

  Page unique portant le formulaire de don (tâche 8) : sélecteur des motifs
  actifs, montant selon le régime du motif choisi, informations du donateur.
  Layout minimal (voir layouts/minimal.blade.php), comme template-rdv et
  template-confirmation : pas de navbar, pas de bouton WhatsApp flottant, son
  propre lien de retour en tient lieu.

  Le contenu vient de partials.don-form ; voir App\View\Composers\Don pour
  les données ($motifs, $motifPreselectionne, $erreurs, $valeurs…).
--}}

@extends('layouts.minimal')

@section('content')
  <div class="don-page">
    <a href="{{ home_url('/') }}" class="don-back">
      <x-icon name="arrow-left" :size="14" />
      <span>Retour au site</span>
    </a>

    @include('partials.don-form')
  </div>
@endsection
