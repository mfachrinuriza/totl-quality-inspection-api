<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('Th_QualityInspection', function (Blueprint $t) {
            $t->increments('ID');
            $t->uuid('TransactionId')->index();        // pengelompok row 1 transaksi (dari iOS)
            $t->uuid('ClientId')->unique();            // id temuan di local DB iOS (anti-duplikat)
            $t->string('Tower', 100)->nullable();
            $t->string('Lantai', 100)->nullable();
            $t->string('Zona', 100)->nullable();
            $t->string('DrawingUrl', 1000)->nullable();
            $t->string('PICTotal', 100)->nullable();
            $t->string('MK', 100)->nullable();
            $t->string('Vendor', 100)->nullable();
            $t->string('Ketidaksesuaian', 1000)->nullable();
            $t->text('DescTemuan')->nullable();
            $t->boolean('Status')->default(true);      // 1 aktif, 0 dihapus (soft delete)
            $t->timestamp('CreatedAt')->useCurrent();
            $t->timestamp('UpdatedAt')->useCurrent();
        });

        Schema::create('Td_QualityInspection_Picture', function (Blueprint $t) {
            $t->increments('ID');
            $t->unsignedInteger('ThQualityInspectionID')->index();
            $t->string('PictureUrl', 1000)->nullable();
            $t->string('Keterangan', 2000)->nullable();
            $t->boolean('Status')->default(true);
            $t->foreign('ThQualityInspectionID')->references('ID')->on('Th_QualityInspection');
        });

        Schema::create('Td_QualityInspection_Pinpoint', function (Blueprint $t) {
            $t->increments('ID');
            $t->unsignedInteger('ThQualityInspectionID')->index();
            $t->string('DrawingUrl', 1000)->nullable();
            $t->string('Keterangan', 5000)->nullable();
            $t->decimal('PinpointX', 18, 12);          // rasio 0..1 terhadap lebar halaman
            $t->decimal('PinpointY', 18, 12);          // rasio 0..1 terhadap tinggi halaman
            $t->integer('PageNumber');
            $t->boolean('Status')->default(true);
            $t->foreign('ThQualityInspectionID')->references('ID')->on('Th_QualityInspection');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Td_QualityInspection_Pinpoint');
        Schema::dropIfExists('Td_QualityInspection_Picture');
        Schema::dropIfExists('Th_QualityInspection');
    }
};
