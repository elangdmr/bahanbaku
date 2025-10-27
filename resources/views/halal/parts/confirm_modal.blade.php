<div class="p-2">
  <div class="small text-muted mb-50">
    <div><strong>ID Permintaan:</strong> {{ $row->kode }}</div>
    <div><strong>Nama Bahan:</strong> {{ $row->bahan_nama ?? '-' }}</div>
  </div>
  <hr class="my-50">

  <form id="halalConfirmForm" method="POST" action="{{ route('halal.confirm.update', $row->id) }}">
    @csrf
    @method('PUT')

    <div class="mb-1">
      <label class="form-label">Tanggal Verifikasi <span class="text-danger">*</span></label>
      <input type="date" name="tgl_verifikasi" class="form-control" required value="{{ old('tgl_verifikasi') }}">
      @error('tgl_verifikasi') <div class="text-danger mt-25">{{ $message }}</div> @enderror
    </div>

    <div class="mb-1">
      <label class="form-label">Konfirmasi Halal <span class="text-danger">*</span></label>
      <select name="hasil_halal" class="form-select" required>
        <option value="" disabled selected>Pilih Hasil...</option>
        <option value="Lulus Halal" {{ old('hasil_halal')==='Lulus Halal' ? 'selected' : '' }}>Lulus Halal</option>
        <option value="Tidak Lulus Halal" {{ old('hasil_halal')==='Tidak Lulus Halal' ? 'selected' : '' }}>Tidak Lulus Halal</option>
      </select>
      @error('hasil_halal') <div class="text-danger mt-25">{{ $message }}</div> @enderror
    </div>

    <div class="text-end mt-2">
      <button type="submit" class="btn btn-primary">Konfirmasi</button>
    </div>
  </form>
</div>
