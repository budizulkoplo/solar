<div class="page-content" style="padding-bottom: 80px;"> 
    {{-- supaya konten paling bawah tidak ketutup navbar/footer --}}

    @if ($histori->isEmpty())
        <div class="alert alert-outline-warning">
            <p>Data Belum Tersedia</p>
        </div>
    @endif

    @foreach ($histori as $d)
        <ul class="listview image-listview mb-3">
            <li>
                <div class="item">
                    @php
                        $path = Storage::url('uploads/absensi/'.$d->foto_in);
                    @endphp
                    <img src="{{ url($path) }}" alt="image" class="image">
                    
                    <div class="in">
                        <div>
                            
                            <small class="text-muted">{{ date('d-m-Y', strtotime($d->tgl_presensi)) }}</small><br>

                            {{-- Preview Google Maps --}}
                            @if(!empty($d->lokasi))
                                <div class="mt-2" style="width: 100%; height: 150px; border-radius:10px; overflow:hidden;">
                                    <iframe 
                                        src="https://www.google.com/maps?q={{ $d->lokasi }}&output=embed" 
                                        width="100%" 
                                        height="150" 
                                        style="border:0;" 
                                        allowfullscreen="" 
                                        loading="lazy">
                                    </iframe>
                                </div>
                            @endif
                        </div>
                        <span class="badge {{ $d->jam_in < "23:00" ? "bg-success" : "bg-success" }}">
                            {{ $d->jam_in }}
                        </span>
                    </div>
                </div>
            </li>
        </ul>
    @endforeach

</div>
