<?php

declare(strict_types=1);

/**
 * Validasi input tamu. Backend adalah sumber validasi utama; validasi
 * JavaScript di frontend hanya membantu UX.
 */

const ALLOWED_VISIT_PURPOSES = ['Minta API Data Tamu'];
const MAX_VISIT_PURPOSE_LENGTH = 500;

function is_valid_phone(string $phone): bool
{
    if (strlen($phone) > 30) {
        return false;
    }

    if (preg_match('/^\+?[0-9][0-9\s\-()]*$/', $phone) !== 1) {
        return false;
    }

    $digits = preg_replace('/[^0-9]/', '', ltrim($phone, '+')) ?? '';
    $count = strlen($digits);

    return $count >= 6 && $count <= 15;
}

/**
 * Validasi data tamu.
 *
 * $source = 'form': keperluan dari mode manual/dropdown (input form HTML).
 * $source = 'api' : keperluan dari field visit_purpose (JSON API).
 *
 * @param array<string, mixed> $input
 * @return array{errors: array<string, string>, data: array<string, string>|null}
 */
function validate_guest(array $input, string $source): array
{
    $errors = [];

    $name = trim((string) ($input['name'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));

    if ($name === '') {
        $errors['name'] = 'Nama wajib diisi.';
    } elseif (mb_strlen($name) > 150) {
        $errors['name'] = 'Nama maksimal 150 karakter.';
    }

    if ($email === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (strlen($email) > 255) {
        $errors['email'] = 'Email maksimal 255 karakter.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if ($phone === '') {
        $errors['phone'] = 'Nomor HP wajib diisi.';
    } elseif (!is_valid_phone($phone)) {
        $errors['phone'] = 'Format nomor HP tidak valid.';
    }

    if ($source === 'form') {
        $mode = (string) ($input['purpose_mode'] ?? '');

        if ($mode === 'dropdown') {
            $purpose = (string) ($input['visit_purpose'] ?? '');
            if (!in_array($purpose, ALLOWED_VISIT_PURPOSES, true)) {
                $errors['visit_purpose'] = 'Pilihan keperluan tidak dikenal.';
            }
        } elseif ($mode === 'manual') {
            $purpose = trim((string) ($input['purpose_custom'] ?? ''));
            if ($purpose === '') {
                $errors['visit_purpose'] = 'Keperluan kunjungan wajib diisi.';
            } elseif (mb_strlen($purpose) > MAX_VISIT_PURPOSE_LENGTH) {
                $errors['visit_purpose'] = 'Keperluan maksimal ' . MAX_VISIT_PURPOSE_LENGTH . ' karakter.';
            }
        } else {
            $purpose = '';
            $errors['visit_purpose'] = 'Pilih salah satu mode pengisian keperluan.';
        }
    } else {
        $purpose = trim((string) ($input['visit_purpose'] ?? ''));
        if ($purpose === '') {
            $errors['visit_purpose'] = 'Keperluan kunjungan wajib diisi.';
        } elseif (mb_strlen($purpose) > MAX_VISIT_PURPOSE_LENGTH) {
            $errors['visit_purpose'] = 'Keperluan maksimal ' . MAX_VISIT_PURPOSE_LENGTH . ' karakter.';
        }
    }

    if ($errors !== []) {
        return ['errors' => $errors, 'data' => null];
    }

    return [
        'errors' => [],
        'data' => [
            'name' => $name,
            'email' => strtolower($email),
            'phone' => $phone,
            'visit_purpose' => $purpose,
        ],
    ];
}
