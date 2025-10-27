<div class="p-3">
  <div class="mb-2">
    <div class="fw-bold">ID Permintaan</div>
    <div>{{ $row->kode }}</div>
  </div>
  <div class="mb-2">
    <div class="fw-bold">Nama Bahan</div>
    <div>{{ $row->bahan_nama ?? '-' }}</div>
  </div>
  <div class="mb-3">
    <div class="fw-bold">Tanggal COA Diterima</div>
    <div>{{ $row->tgl_coa_diterima ? \Carbon\Carbon::parse($row->tgl_coa_diterima)->format('d/m/Y') : '-' }}</div>
  </div>

  <form action="{{ route('uji-coa.confirm.update', $row->id) }}" method="POST"
        onsubmit="this.querySelector('button[type=submit]').disabled=true;">
    @csrf
    @method('PUT')

    <label class="form-label">Hasil Uji</label>
    <select name="hasil_uji" class="form-select mb-3" required>
      <option value="" selected disabled>Pilih Hasil…</option>
      <option value="Lulus">Lulus Uji COA</option>
      <option value="Tidak Lulus">Tidak Lulus Uji COA</option>
    </select>

    <div class="d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
      <button type="submit" class="btn btn-success">Konfirmasi</button>
    </div>
  </form>
</div>
