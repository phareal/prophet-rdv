{{--
  Formulaire de don (tâche 8). Poste sur admin-post.php, traité par
  ProphetCore\Don\DonSubmitHandler : chaque `name` ci-dessous correspond à
  une clé lue par MontantDon::resoudre() ou validée par
  DonSubmitHandler::validerDonateur(). Les erreurs et les valeurs viennent de
  FlashStore::pull() via le composer Don, jamais d'un toast — même
  convention que partials/rdv-form.blade.php.

  Le bloc montant est piloté par Alpine (donForm, resources/js/app.js) selon
  le motif choisi : les trois régimes (libre, fixe, suggéré) sont tous
  rendus côté serveur ci-dessous, Alpine se contente d'afficher celui qui
  correspond. En régime fixe, aucun champ `montant` n'est soumis — le
  serveur reprend celui du motif de toute façon (MontantDon::resoudre()).
--}}
<div class="sec-don don" id="don"
     x-data="donForm(@js($motifs), @js($motifPreselectionne), @js($devise), @js($valeurs['montant'] ?? ''))">
  <div class="don__inner">

      <header class="don__header">
        <p class="don__eyebrow"><x-icon name="heart" :size="12" /> Don</p>
        <h1 class="don__heading">
          Soutenir le <span class="don__heading-accent">ministère</span>
        </h1>
        <p class="don__subheading">
          Choisissez un motif et réglez en ligne, en toute sécurité.
        </p>
      </header>

      @if ($motifIndisponible)
        <p class="don__notice" role="status">
          Le motif « {{ $motifIndisponible }} » n'est plus disponible. Choisissez parmi les motifs proposés ci-dessous.
        </p>
      @endif

      @if (empty($motifs))
        <p class="don__empty">
          Aucun motif de don n'est disponible pour le moment. Merci de repasser plus tard.
        </p>
      @else
        <form class="don__form" id="formulaire-don" method="post" novalidate
              action="{{ esc_url(admin_url('admin-post.php')) }}"
              x-on:submit="envoi = true">

          <input type="hidden" name="action" value="{{ $actionDon }}">
          @php(wp_nonce_field(\ProphetCore\Don\DonSubmitHandler::NONCE, 'prophet_nonce'))

          {{-- Pot de miel : masqué visuellement (hors-écran), jamais display:none, jamais atteint par tabulation. --}}
          <div class="pot-de-miel" aria-hidden="true">
            <label>Site web
              <input type="text" name="{{ \ProphetCore\Don\DonSubmitHandler::HONEYPOT }}" tabindex="-1" autocomplete="off">
            </label>
          </div>

          @if ($erreurGlobale)
            <p class="don__error" role="alert">{{ $erreurGlobale }}</p>
          @endif

          {{-- ─── MOTIF ─── --}}
          <div class="don__field">
            <label for="motif" class="don__label">Motif <span class="don__required">*</span></label>
            <select id="motif" name="motif" x-model.number="motifId" required
                    class="don__input don__select @if (isset($erreurs['motif'])) don__input--err @endif"
                    @if (isset($erreurs['motif'])) aria-invalid="true" aria-describedby="erreur-motif" @endif>
              <option value="">Choisissez un motif</option>
              @foreach ($motifs as $motif)
                <option value="{{ $motif['id'] }}" @if ((int) $motifPreselectionne === $motif['id']) selected @endif>
                  {{ $motif['titre'] }}
                </option>
              @endforeach
            </select>
            @if (isset($erreurs['motif']))
              <p class="don__error" id="erreur-motif">{{ $erreurs['motif'] }}</p>
            @endif
            <p class="don__motif-description" x-show="motif?.description" x-text="motif?.description" style="display:none"></p>
          </div>

          {{-- ─── MONTANT (régime du motif choisi) ─── --}}
          <template x-if="motif?.regime === 'fixe'">
            <div class="don__field">
              <span class="don__label">Montant</span>
              <p class="don__montant-fixe">
                <strong x-text="`${motif.montant_fixe} ${devise}`"></strong>
              </p>
            </div>
          </template>

          <template x-if="motif?.regime === 'suggere'">
            <div class="don__field">
              <label for="montant" class="don__label">Montant <span class="don__required">*</span></label>
              <div class="don__paliers">
                <template x-for="palier in motif.suggeres" :key="palier">
                  <button type="button" class="don__palier"
                          x-bind:class="{ 'don__palier--active': montant === palier }"
                          x-on:click="montant = palier"
                          x-text="`${palier} ${devise}`"></button>
                </template>
              </div>
              <input id="montant" type="number" name="montant" x-model.number="montant"
                     value="{{ $valeurs['montant'] ?? '' }}"
                     x-bind:min="motif?.min" x-bind:max="motif?.max" required
                     placeholder="Ou un autre montant"
                     class="don__input @if (isset($erreurs['montant'])) don__input--err @endif"
                     @if (isset($erreurs['montant'])) aria-invalid="true" aria-describedby="erreur-montant" @endif>
            </div>
          </template>

          <template x-if="motif?.regime === 'libre'">
            <div class="don__field">
              <label for="montant" class="don__label">
                Montant (<span x-text="devise"></span>) <span class="don__required">*</span>
              </label>
              <input id="montant" type="number" name="montant" x-model.number="montant"
                     value="{{ $valeurs['montant'] ?? '' }}"
                     x-bind:min="motif?.min" x-bind:max="motif?.max" required
                     placeholder="Montant de votre don"
                     class="don__input @if (isset($erreurs['montant'])) don__input--err @endif"
                     @if (isset($erreurs['montant'])) aria-invalid="true" aria-describedby="erreur-montant" @endif>
            </div>
          </template>

          @if (isset($erreurs['montant']))
            <p class="don__error" id="erreur-montant">{{ $erreurs['montant'] }}</p>
          @endif

          <div class="don__row">
            <div class="don__field">
              <label for="prenom" class="don__label">Prénom <span class="don__required">*</span></label>
              <input id="prenom" type="text" name="prenom" value="{{ $valeurs['prenom'] ?? '' }}"
                     placeholder="Votre prénom"
                     class="don__input @if (isset($erreurs['prenom'])) don__input--err @endif"
                     @if (isset($erreurs['prenom'])) aria-invalid="true" aria-describedby="erreur-prenom" @endif>
              @if (isset($erreurs['prenom']))
                <p class="don__error" id="erreur-prenom">{{ $erreurs['prenom'] }}</p>
              @endif
            </div>
            <div class="don__field">
              <label for="nom" class="don__label">Nom <span class="don__required">*</span></label>
              <input id="nom" type="text" name="nom" value="{{ $valeurs['nom'] ?? '' }}"
                     placeholder="Votre nom de famille"
                     class="don__input @if (isset($erreurs['nom'])) don__input--err @endif"
                     @if (isset($erreurs['nom'])) aria-invalid="true" aria-describedby="erreur-nom" @endif>
              @if (isset($erreurs['nom']))
                <p class="don__error" id="erreur-nom">{{ $erreurs['nom'] }}</p>
              @endif
            </div>
          </div>

          <div class="don__row">
            <div class="don__field">
              <label for="email" class="don__label">Email <span class="don__required">*</span></label>
              <input id="email" type="email" name="email" value="{{ $valeurs['email'] ?? '' }}"
                     placeholder="votre@email.com"
                     class="don__input @if (isset($erreurs['email'])) don__input--err @endif"
                     @if (isset($erreurs['email'])) aria-invalid="true" aria-describedby="erreur-email" @endif>
              @if (isset($erreurs['email']))
                <p class="don__error" id="erreur-email">{{ $erreurs['email'] }}</p>
              @endif
            </div>
            <div class="don__field">
              <label for="telephone" class="don__label">
                Téléphone <span class="don__label-optional">(facultatif)</span>
              </label>
              <input id="telephone" type="tel" name="telephone" value="{{ $valeurs['telephone'] ?? '' }}"
                     placeholder="+33 6 12 34 56 78"
                     class="don__input">
            </div>
          </div>

          <div class="don__field">
            <label for="message" class="don__label">
              Message <span class="don__label-optional">(facultatif)</span>
            </label>
            <textarea id="message" name="message" maxlength="500"
                      placeholder="Un mot pour accompagner votre don…"
                      class="don__input don__input--textarea">{{ $valeurs['message'] ?? '' }}</textarea>
          </div>

          <div class="don__note">
            <p>Vous serez redirigé vers notre prestataire de paiement pour régler en toute sécurité.</p>
          </div>

          <button type="submit" class="don__cta" x-bind:disabled="envoi">
            <span x-show="!envoi"><x-icon name="heart" :size="16" /> Faire mon don</span>
            <span x-show="envoi" style="display:none"><x-icon name="loader-2" :size="16" class="don__cta-spin" /> Envoi…</span>
          </button>
        </form>
      @endif

  </div>
</div>
