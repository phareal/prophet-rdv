{{--
  Port de components/RdvForm.vue (template) et components/ConsultationPicker.vue.
  La validation vee-validate/zod + le `$fetch` JSON de la source sont
  remplacés par un POST classique vers admin-post.php, traité par
  ProphetCore\Rdv\SubmitHandler (tâche 13) : chaque `name` ci-dessous
  correspond exactement à une clé lue par Validator::validate() (tâche 11).
  Les erreurs et les valeurs viennent de FlashStore::pull() via le composer
  Rdv (tâche 12), jamais d'un toast.

  Type de consultation et créneau horaire : RdvForm.vue pilote ces choix
  avec de vrais <button> cliqués en JS (ConsultationPicker.vue, boucle sur
  HEURES) et transmet la valeur via $fetch JSON. Le round-trip serveur exige
  un champ de formulaire réellement nommé ; ces boutons deviennent donc des
  <label> enveloppant un <input type="radio"> masqué avec `.sr-only`
  (jamais display:none), en conservant telles quelles les classes visuelles
  d'origine (cp__item, fp__time-slot) sur le label. Voir le commentaire en
  tête de _rdv-form.css.
--}}
<div class="sec-rdv-form fp">
  <div class="fp__inner">

    {{-- ── EN-TÊTE ── --}}
    <header class="fp__header">
      <p class="fp__eyebrow">{{ $evenement ? 'Inscription événement' : 'Rendez-vous' }}</p>
      <h2 class="fp__heading">
        {{ $evenement ? 'Réserver ma' : 'Demander une' }}<br>
        <span class="fp__heading-accent">{{ $evenement ? 'place' : 'consultation' }}</span>
      </h2>
      <p class="fp__subheading">
        Remplissez le formulaire. Vous recevrez une confirmation par email et WhatsApp.
      </p>
    </header>

    {{-- ── BANDEAU ÉVÉNEMENT ── --}}
    @if ($evenement)
      <div class="fp__event-banner">
        <x-icon name="calendar-days" :size="14" class="fp__event-banner__icon" />
        <div class="fp__event-banner__body">
          <span class="fp__event-banner__title">{{ $evenement['titre'] }}</span>
          <span class="fp__event-banner__meta">
            <x-icon name="map-pin" :size="11" />{{ $evenement['lieu'] }}
            <x-icon name="clock" :size="11" />{{ $evenement['heure'] }}
          </span>
        </div>
      </div>
    @endif

    <form class="fp__form" id="formulaire" method="post" novalidate
          action="{{ esc_url(admin_url('admin-post.php')) }}"
          x-data="rdvForm({
            type: @js($valeurs['type_consultation'] ?? ($evenement['type'] ?? '')),
            heure: @js($valeurs['heure'] ?? ''),
            paiement: @js($valeurs['mode_paiement'] ?? ''),
            message: @js($valeurs['message'] ?? ($evenement['message'] ?? '')),
          })">

      <input type="hidden" name="action" value="{{ \ProphetCore\Rdv\SubmitHandler::ACTION }}">
      @php(wp_nonce_field(\ProphetCore\Rdv\SubmitHandler::NONCE, 'prophet_nonce'))

      {{-- Pot de miel : masqué visuellement (hors-écran), jamais display:none, jamais atteint par tabulation. --}}
      <div class="pot-de-miel" aria-hidden="true">
        <label>Site web
          <input type="text" name="{{ \ProphetCore\Rdv\SubmitHandler::HONEYPOT }}" tabindex="-1" autocomplete="off">
        </label>
      </div>

      @if ($erreurGlobale)
        <p class="fp__error" role="alert">{{ $erreurGlobale }}</p>
      @endif

      {{-- ─── TYPE DE CONSULTATION ─── --}}
      <fieldset class="fp__section">
        <legend class="fp__section-legend">
          Type de consultation <span class="fp__required">*</span>
        </legend>

        @if ($evenement)
          <div class="fp__consultation-badge">{{ $evenement['type'] }}</div>
          <input type="hidden" name="type_consultation" value="{{ $evenement['type'] }}">
        @else
          <div class="cp">
            <div class="cp__grid" role="radiogroup" aria-label="Type de consultation"
                 @if (isset($erreurs['type_consultation'])) aria-describedby="erreur-type_consultation" @endif>
              @foreach ($services as $service)
                <label class="cp__item" x-bind:class="{ 'cp__item--active': type === @js($service['titre']) }">
                  <input type="radio" class="sr-only" name="type_consultation" value="{{ $service['titre'] }}"
                         x-model="type" required
                         @if (($valeurs['type_consultation'] ?? '') === $service['titre']) checked @endif>
                  <x-icon :name="$service['icone']" :size="14" class="cp__item-icon" />
                  <span class="cp__item-label">{{ $service['titre'] }}</span>
                </label>
              @endforeach
            </div>
            <div class="cp__status">
              <p class="cp__status-selected" x-show="type" x-text="type" style="display:none"></p>
              <p class="cp__status-empty" x-show="!type">Choisissez un type de consultation</p>
            </div>
          </div>
          @if (isset($erreurs['type_consultation']))
            <p class="fp__error" id="erreur-type_consultation">{{ $erreurs['type_consultation'] }}</p>
          @endif
        @endif
      </fieldset>

      <div class="fp__rule"></div>

      {{-- ─── NOM / PRÉNOM ─── --}}
      <div class="fp__row">
        <div class="fp__field">
          <label for="nom" class="fp__label">Nom <span class="fp__required">*</span></label>
          <input id="nom" type="text" name="nom" value="{{ $valeurs['nom'] ?? '' }}"
                 placeholder="Votre nom de famille"
                 class="fp__input @if (isset($erreurs['nom'])) fp__input--err @endif"
                 @if (isset($erreurs['nom'])) aria-invalid="true" aria-describedby="erreur-nom" @endif>
          @if (isset($erreurs['nom']))
            <p class="fp__error" id="erreur-nom">{{ $erreurs['nom'] }}</p>
          @endif
        </div>
        <div class="fp__field">
          <label for="prenom" class="fp__label">Prénom <span class="fp__required">*</span></label>
          <input id="prenom" type="text" name="prenom" value="{{ $valeurs['prenom'] ?? '' }}"
                 placeholder="Votre prénom"
                 class="fp__input @if (isset($erreurs['prenom'])) fp__input--err @endif"
                 @if (isset($erreurs['prenom'])) aria-invalid="true" aria-describedby="erreur-prenom" @endif>
          @if (isset($erreurs['prenom']))
            <p class="fp__error" id="erreur-prenom">{{ $erreurs['prenom'] }}</p>
          @endif
        </div>
      </div>

      {{-- ─── EMAIL / TÉLÉPHONE ─── --}}
      <div class="fp__row">
        <div class="fp__field">
          <label for="email" class="fp__label">Email <span class="fp__required">*</span></label>
          <input id="email" type="email" name="email" value="{{ $valeurs['email'] ?? '' }}"
                 placeholder="votre@email.com"
                 class="fp__input @if (isset($erreurs['email'])) fp__input--err @endif"
                 @if (isset($erreurs['email'])) aria-invalid="true" aria-describedby="erreur-email" @endif>
          @if (isset($erreurs['email']))
            <p class="fp__error" id="erreur-email">{{ $erreurs['email'] }}</p>
          @endif
        </div>
        <div class="fp__field">
          <label for="telephone" class="fp__label">
            Téléphone / WhatsApp <span class="fp__required">*</span>
          </label>
          <input id="telephone" type="tel" name="telephone" value="{{ $valeurs['telephone'] ?? '' }}"
                 placeholder="+33 6 12 34 56 78"
                 class="fp__input fp__input--mono @if (isset($erreurs['telephone'])) fp__input--err @endif"
                 @if (isset($erreurs['telephone'])) aria-invalid="true" aria-describedby="erreur-telephone" @endif>
          @if (isset($erreurs['telephone']))
            <p class="fp__error" id="erreur-telephone">{{ $erreurs['telephone'] }}</p>
          @endif
        </div>
      </div>

      {{-- ─── PAYS / DATE ─── --}}
      <div class="fp__row">
        <div class="fp__field">
          <label for="pays" class="fp__label">
            Pays de résidence <span class="fp__required">*</span>
          </label>
          <input id="pays" type="text" name="pays" value="{{ $valeurs['pays'] ?? '' }}"
                 placeholder="France, Côte d'Ivoire…"
                 class="fp__input @if (isset($erreurs['pays'])) fp__input--err @endif"
                 @if (isset($erreurs['pays'])) aria-invalid="true" aria-describedby="erreur-pays" @endif>
          @if (isset($erreurs['pays']))
            <p class="fp__error" id="erreur-pays">{{ $erreurs['pays'] }}</p>
          @endif
        </div>
        <div class="fp__field">
          <label for="date" class="fp__label">Date souhaitée <span class="fp__required">*</span></label>
          {{-- flatpickr se greffe sur ce champ (resources/js/app.js) : locale FR, dimanches et
               aujourd'hui désactivés, même règle que Validator::validate(). --}}
          <input id="date" type="text" name="date" data-flatpickr
                 value="{{ $valeurs['date'] ?? ($evenement['date'] ?? '') }}"
                 placeholder="Choisir une date"
                 @if ($evenement) readonly @endif
                 class="fp__input @if ($evenement) fp__input--locked @endif @if (isset($erreurs['date'])) fp__input--err @endif"
                 @if (isset($erreurs['date'])) aria-invalid="true" aria-describedby="erreur-date" @endif>
          @if (isset($erreurs['date']))
            <p class="fp__error" id="erreur-date">{{ $erreurs['date'] }}</p>
          @endif
        </div>
      </div>

      {{-- ─── GRILLE BAS ─── --}}
      <div class="fp__bottom">

        {{-- col gauche : heure + message --}}
        <div class="fp__bottom-col">
          <div class="fp__field">
            <label class="fp__label">Heure souhaitée <span class="fp__required">*</span></label>
            <div class="fp__time-grid" role="radiogroup" aria-label="Heure souhaitée"
                 @if (isset($erreurs['heure'])) aria-describedby="erreur-heure" @endif>
              @foreach ($heures as $h)
                <label class="fp__time-slot" x-bind:class="{ 'fp__time-slot--active': heure === @js($h) }">
                  <input type="radio" class="sr-only" name="heure" value="{{ $h }}" x-model="heure" required
                         @if (($valeurs['heure'] ?? '') === $h) checked @endif>
                  {{ $h }}
                </label>
              @endforeach
            </div>
            @if (isset($erreurs['heure']))
              <p class="fp__error" id="erreur-heure">{{ $erreurs['heure'] }}</p>
            @endif
          </div>

          <div class="fp__field fp__field--grow">
            <div class="fp__field-header">
              <label for="message" class="fp__label">
                Message <span class="fp__label-optional">(facultatif)</span>
              </label>
              <span class="fp__char-count" x-text="`${message.length}/500`"></span>
            </div>
            <textarea id="message" name="message" maxlength="500" x-model="message"
                      placeholder="Décrivez votre situation…"
                      class="fp__input fp__input--textarea fp__input--grow @if (isset($erreurs['message'])) fp__input--err @endif"
                      @if (isset($erreurs['message'])) aria-invalid="true" aria-describedby="erreur-message" @endif></textarea>
            @if (isset($erreurs['message']))
              <p class="fp__error" id="erreur-message">{{ $erreurs['message'] }}</p>
            @endif
          </div>
        </div>

        {{-- col droite : paiement + note + CTA --}}
        <div class="fp__bottom-col">
          <div class="fp__field">
            <label for="mode_paiement" class="fp__label">Mode de paiement <span class="fp__required">*</span></label>
            <select id="mode_paiement" name="mode_paiement" x-model="paiement"
                    class="fp__input fp__select @if (isset($erreurs['mode_paiement'])) fp__input--err @endif"
                    @if (isset($erreurs['mode_paiement'])) aria-invalid="true" aria-describedby="erreur-mode_paiement" @endif>
              <option value="" disabled @if (empty($valeurs['mode_paiement'] ?? '')) selected @endif>Choisir…</option>
              @foreach ($modes as $mode)
                <option value="{{ $mode['value'] }}" @if (($valeurs['mode_paiement'] ?? '') === $mode['value']) selected @endif>
                  {{ $mode['label'] }}
                </option>
              @endforeach
            </select>
            @if (isset($erreurs['mode_paiement']))
              <p class="fp__error" id="erreur-mode_paiement">{{ $erreurs['mode_paiement'] }}</p>
            @endif
          </div>

          <div class="fp__note">
            <p>
              <strong>Note :</strong>
              Demande traitée en 24–48h. Vous serez redirigé vers WhatsApp pour confirmer.
            </p>
          </div>

          <button type="submit" class="fp__cta" x-bind:disabled="envoi" x-on:click="envoi = true">
            <span x-show="!envoi"><x-icon name="send" :size="16" /> Envoyer ma demande</span>
            <span x-show="envoi" style="display:none"><x-icon name="loader-2" :size="16" class="fp__cta-spin" /> Envoi…</span>
          </button>
        </div>

      </div>

    </form>
  </div>
</div>
