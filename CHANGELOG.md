# Changelog forka

Zmiany w gałęzi `cakephp5-clean` forka `michaltryniecki/croogo`. Historia do
`v5.0.0-alpha.2` jest w opisach PR-ów (#42–#50); ten plik prowadzimy od kolejnego
wydania.

## Niewydane

### Poprawione

- **Formularze i linki w zwykłych modalach Bootstrapa działają.** `Admin.modal()`
  (`Core/webroot/js/core/modal.js`) przechwytywał `submit` każdego formularza
  i `click` każdego linku w `.modal-dialog`, nie tylko w modalach z treścią
  ładowaną przez XHR. Formularz w modalu aplikacji nie wysyłał się (przycisk
  dostawał spinner z `Admin.formFeedback`, żądanie nie wychodziło), a link ładował
  się w treść modala. Teraz handlery działają tylko w modalu oznaczonym
  `data-remote-loaded` — znacznik ustawia ten, kto ładuje treść (`data-remote`
  w `modal.js`, okno wyboru w `choose.js`), i jest zdejmowany po zamknięciu modala.
  Ręcznie ładowaną treść oznacza się przez `Admin.modal.markRemote($modal)`.
- **Formularz w modalu zdalnym wysyła się od pierwszego kliknięcia.** Handler
  `submit` przy każdym wysłaniu bindował kolejny `$form.submit(...)`: pierwsze
  kliknięcie nic nie wysyłało, każde następne wysyłało o jedno żądanie więcej.
  Teraz jeden handler wysyła od razu i używa metody z atrybutu `method` formularza.
  Przy błędzie odpowiedzi przyciski wracają ze spinnera (`Admin.resetFormFeedback`).
  Linki i formularze w nagłówku i stopce modala zdalnego nie są już przechwytywane
  (wcześniej ładowały się „donikąd”).
- **Okno wyboru linku otwiera się pod Bootstrapem 5.** Link w `LinkChooser`
  (pole linku w Menus, ustawienia typu `link`) miał tylko `data-target`, a
  Bootstrap 5 szuka modala po `data-bs-target` — kliknięcie rzucało wyjątek w
  data-api i lista ładowała się do niewidocznego modala. Dodany `data-bs-target`
  (`data-target` zostaje, czyta go `choose.js`).
- **Wybór w oknie wyboru wywołuje `chooserSelect` raz.** `choose.js` przy każdym
  otwarciu dokładał do modala kolejny handler `click` na pozycjach, więc po
  N otwarciach wybór wywoływał zdarzenie N razy, także na polach, które korzystały
  z tego modala wcześniej.
