{{--
  Template Name: Rendez-vous

  Port de pages/rdv.vue. Le squelette du brief ne montrait qu'un
  <div class="page-rdv"> enveloppant les deux partiels ; la source réelle
  porte en plus le bouton retour (.rdv-back) et la mise en page à deux
  colonnes (<section class="rdv-layout"> / .rdv-layout__left / __right),
  et sa classe racine est `.rdv-page`, pas `.page-rdv` — voir _page-rdv.css,
  qui porte le bloc <style> non scopé de pages/rdv.vue tel quel.

  Layout minimal (voir layouts/minimal.blade.php) : pages/rdv.vue ne montait
  ni TheNavbar, ni AppFooter, ni FloatingWhatsApp — son propre lien
  « Retour au site » (.rdv-back ci-dessous) en tient déjà lieu.
--}}

@extends('layouts.minimal')

@section('content')
  <div class="rdv-page">
    <a href="{{ home_url('/') }}" class="rdv-back">
      <x-icon name="arrow-left" :size="14" />
      <span>Retour au site</span>
    </a>

    <section id="rdv" class="rdv-layout">
      <div class="rdv-layout__left">
        @include('partials.hero-left')
      </div>
      <div class="rdv-layout__right">
        @include('partials.rdv-form')
      </div>
    </section>
  </div>
@endsection
