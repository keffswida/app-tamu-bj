(function () {
  'use strict';

  var form = document.getElementById('guest-form');
  if (!form) {
    return;
  }

  var nameInput = document.getElementById('name');
  var emailInput = document.getElementById('email');
  var phoneInput = document.getElementById('phone');
  var customInput = document.getElementById('purpose-custom');
  var selectInput = document.getElementById('purpose-select');
  var segmented = form.querySelectorAll('.segmented');

  var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var phonePattern = /^\+?[0-9][0-9\s\-()]*$/;

  function currentMode() {
    var checked = form.querySelector('input[name="purpose_mode"]:checked');
    return checked ? checked.value : 'manual';
  }

  function fieldContainer(input) {
    return input.closest('.field') || input.parentElement;
  }

  function clearError(input) {
    input.classList.remove('is-invalid');
    var container = fieldContainer(input);
    var errors = container.querySelectorAll('.field-error');
    for (var i = 0; i < errors.length; i++) {
      errors[i].remove();
    }
  }

  function setError(input, message) {
    input.classList.add('is-invalid');
    var container = fieldContainer(input);
    var existing = container.querySelector('.field-error');
    if (!existing) {
      var p = document.createElement('p');
      p.className = 'field-error';
      p.textContent = message;
      input.insertAdjacentElement('afterend', p);
    }
  }

  function applyMode() {
    var mode = currentMode();
    var isManual = mode === 'manual';

    customInput.disabled = !isManual;
    customInput.required = isManual;
    selectInput.disabled = isManual;
    selectInput.required = !isManual;

    for (var i = 0; i < segmented.length; i++) {
      var radio = segmented[i].querySelector('input[name="purpose_mode"]');
      segmented[i].classList.toggle('is-active', !!radio && radio.checked);
    }
  }

  function validate() {
    var valid = true;

    clearError(nameInput);
    clearError(emailInput);
    clearError(phoneInput);
    clearError(customInput);
    clearError(selectInput);

    var name = nameInput.value.trim();
    if (!name) {
      setError(nameInput, 'Nama wajib diisi.');
      valid = false;
    } else if (name.length > 150) {
      setError(nameInput, 'Nama maksimal 150 karakter.');
      valid = false;
    }

    var email = emailInput.value.trim();
    if (!email) {
      setError(emailInput, 'Email wajib diisi.');
      valid = false;
    } else if (!emailPattern.test(email)) {
      setError(emailInput, 'Format email tidak valid.');
      valid = false;
    }

    var phone = phoneInput.value.trim();
    var phoneDigits = phone.replace(/^\+/, '').replace(/\D/g, '');
    if (!phone) {
      setError(phoneInput, 'Nomor HP wajib diisi.');
      valid = false;
    } else if (!phonePattern.test(phone) || phoneDigits.length < 6 || phoneDigits.length > 15) {
      setError(phoneInput, 'Format nomor HP tidak valid.');
      valid = false;
    }

    if (currentMode() === 'manual') {
      var purpose = customInput.value.trim();
      if (!purpose) {
        setError(customInput, 'Keperluan kunjungan wajib diisi.');
        valid = false;
      } else if (purpose.length > 500) {
        setError(customInput, 'Keperluan maksimal 500 karakter.');
        valid = false;
      }
    }

    return valid;
  }

  form.addEventListener('change', function (event) {
    if (event.target.name === 'purpose_mode') {
      applyMode();
      clearError(customInput);
      clearError(selectInput);
    }
  });

  form.addEventListener('input', function (event) {
    if (event.target.matches('input, select')) {
      clearError(event.target);
    }
  });

  form.addEventListener('submit', function (event) {
    if (!validate()) {
      event.preventDefault();
    }
  });

  applyMode();
})();
