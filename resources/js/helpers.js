export function formatRupiah(angka, prefix = "Rp") {
    if (typeof angka !== "number") {
        angka = parseFloat(angka);
        if (isNaN(angka)) return "";
    }

    return angka.toLocaleString("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0
    }).replace("Rp", prefix);
}