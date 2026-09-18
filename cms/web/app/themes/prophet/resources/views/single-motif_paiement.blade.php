{{--
  Vue de secours de WordPress pour la requête singulière du CPT
  `motif_paiement` : c'est le lien profond (`/don/<slug>/`, réécriture posée
  par ProphetCore\PostTypes\MotifPaiement::register()) que le QR code de
  chaque motif encode. Elle rend exactement le même contenu que
  template-don.blade.php — « ouvre la même page avec le motif présélectionné »
  (spec, section « Les liens profonds ») — jamais une fiche dédiée à ce
  post : la résolution du motif présélectionné (actif ou non) se fait côté
  composer, voir App\View\Composers\Don::motifDuLienProfond().

  Aucun `page_template` à poser ici : la hiérarchie de gabarits de WordPress
  choisit ce fichier pour toute requête singulière sur le CPT sans autre
  configuration.
--}}

@extends('layouts.minimal')

@section('content')
  <div class="don-page">
    <a href="{{ home_url('/don/') }}" class="don-back">
      <x-icon name="arrow-left" :size="14" />
      <span>Tous les motifs</span>
    </a>

    @include('partials.don-form')
  </div>
@endsection
