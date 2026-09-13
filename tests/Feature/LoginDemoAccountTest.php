<?php

it('menampilkan kredensial akun demo di bawah form login', function () {
    $this->get('/admin/login')
        ->assertSuccessful()
        ->assertSee('Akun Demo')
        ->assertSee('Gunakan salah satu akun berikut untuk masuk dan mencoba aplikasi.')
        ->assertSee('superadmin@pos.test')
        ->assertSee('admin@pos.test')
        ->assertSee('manager@pos.test')
        ->assertSee('kasir@pos.test');
});
