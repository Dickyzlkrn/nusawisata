<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds for Indonesian top destinations.
     */
    public function run(): void
    {
        $destinations = [
            [
                'province' => 'Bali',
                'name' => 'Tanah Lot',
                'category' => 'Budaya',
                'description' => 'Pura megah di atas bongkahan batu karang besar di pesisir laut, terkenal dengan panorama matahari terbenam yang memukau dan ombak samudera Hindia.',
                'price' => 60000,
                'latitude' => -8.621213,
                'longitude' => 115.086796,
                'google_rating' => 4.7,
                'review_count' => 84200,
                'image' => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Bali',
                'name' => 'Pantai Kelingking Nusa Penida',
                'category' => 'Alam',
                'description' => 'Tebing karang menyerupai T-Rex yang menjorok ke laut biru toska berpasir putih murni, salah satu ikon pemandangan alam paling spektakuler di dunia.',
                'price' => 25000,
                'latitude' => -8.750849,
                'longitude' => 115.474678,
                'google_rating' => 4.8,
                'review_count' => 31500,
                'image' => 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Bali',
                'name' => 'Pura Ulun Danu Beratan',
                'category' => 'Budaya',
                'description' => 'Pura air suci di tepi Danau Beratan Bedugul yang sejuk berkabut, tampak terapung magis di atas permukaan air danau pegunungan.',
                'price' => 50000,
                'latitude' => -8.275179,
                'longitude' => 115.166667,
                'google_rating' => 4.7,
                'review_count' => 45800,
                'image' => 'https://images.unsplash.com/photo-1555400038-63f5ba517a47?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Jawa Tengah',
                'name' => 'Candi Borobudur',
                'category' => 'Sejarah',
                'description' => 'Candi Buddha terbesar di dunia yang merupakan Situs Warisan Dunia UNESCO, dihiasi ribuan panel relief filosofis dan stupa berlubang megah.',
                'price' => 50000,
                'latitude' => -7.607874,
                'longitude' => 110.203751,
                'google_rating' => 4.8,
                'review_count' => 96300,
                'image' => 'https://images.unsplash.com/photo-1596402184320-417e7178b2cd?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'DI Yogyakarta',
                'name' => 'Candi Prambanan',
                'category' => 'Sejarah',
                'description' => 'Kompleks percandian Hindu megah abad ke-9 yang menjulang tinggi dengan relief kisah epos Ramayana serta pertunjukan sendratari mempesona.',
                'price' => 50000,
                'latitude' => -7.752021,
                'longitude' => 110.491467,
                'google_rating' => 4.7,
                'review_count' => 78400,
                'image' => 'https://images.unsplash.com/photo-1584810359583-96fc3448beaa?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Jawa Timur',
                'name' => 'Gunung Bromo',
                'category' => 'Alam',
                'description' => 'Lautan pasir berbisik dan kawah aktif spektakuler di Kaldera Tengger, terkenal dengan sunrise emas mempesona dari bukit Penanjakan.',
                'price' => 34000,
                'latitude' => -7.942494,
                'longitude' => 112.953012,
                'google_rating' => 4.8,
                'review_count' => 52100,
                'image' => 'https://images.unsplash.com/photo-1588668214407-6ea9a6d8c272?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Jawa Timur',
                'name' => 'Kawah Ijen',
                'category' => 'Alam',
                'description' => 'Danau kawah asam toska terbesar di dunia dengan fenomena api biru (blue fire) langka yang memukau penjelajah di malam hari.',
                'price' => 20000,
                'latitude' => -8.058333,
                'longitude' => 114.241667,
                'google_rating' => 4.7,
                'review_count' => 28400,
                'image' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Nusa Tenggara Timur',
                'name' => 'Pulau Padar Labuan Bajo',
                'category' => 'Bahari',
                'description' => 'Puncak bukit legendaris yang menghadap ke tiga teluk dengan warna pasir berbeda (putih, merah muda, dan hitam) di Taman Nasional Komodo.',
                'price' => 150000,
                'latitude' => -8.653611,
                'longitude' => 119.575000,
                'google_rating' => 4.9,
                'review_count' => 19700,
                'image' => 'https://images.unsplash.com/photo-1570789210967-2cac24afeb00?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Papua Barat Daya',
                'name' => 'Kepulauan Raja Ampat (Piaynemo)',
                'category' => 'Bahari',
                'description' => 'Gugusan pulau karang karst kecil di atas laut biru kehijauan yang jernih, surga terumbu karang terkaya di bumi dengan ratusan spesies ikan langka.',
                'price' => 500000,
                'latitude' => -0.560731,
                'longitude' => 130.272186,
                'google_rating' => 4.9,
                'review_count' => 14300,
                'image' => 'https://images.unsplash.com/photo-1516690561799-46d8f74f9abf?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Sumatera Utara',
                'name' => 'Danau Toba & Pulau Samosir',
                'category' => 'Alam',
                'description' => 'Danau vulkanik kaldera terbesar di dunia dengan panorama pegunungan memukau, kebudayaan Batak yang otentik, dan udara sejuk menyegarkan.',
                'price' => 10000,
                'latitude' => 2.684534,
                'longitude' => 98.875605,
                'google_rating' => 4.7,
                'review_count' => 41200,
                'image' => 'https://images.unsplash.com/photo-1563298723-dcfebaa392e3?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Sumatera Barat',
                'name' => 'Lembah Harau & Ngarai Sianok',
                'category' => 'Alam',
                'description' => 'Lembah tebing granit curam setinggi 100-500 meter yang dihiasi air terjun indah dan hamparan sawah hijau permadani di Bukittinggi.',
                'price' => 15000,
                'latitude' => -0.106667,
                'longitude' => 100.672222,
                'google_rating' => 4.7,
                'review_count' => 18900,
                'image' => 'https://images.unsplash.com/photo-1568084680786-a84f91d1153c?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Jawa Barat',
                'name' => 'Kawah Putih Ciwidey',
                'category' => 'Alam',
                'description' => 'Danau kawah belerang berwarna putih kehijauan yang magis di ketinggian 2.434 mdpl pegunungan Bandung Selatan berhawa dingin.',
                'price' => 28000,
                'latitude' => -7.166204,
                'longitude' => 107.402128,
                'google_rating' => 4.6,
                'review_count' => 38700,
                'image' => 'https://images.unsplash.com/photo-1601000938259-9e92002320b2?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'DKI Jakarta',
                'name' => 'Monumen Nasional (Monas)',
                'category' => 'Sejarah',
                'description' => 'Tugu peringatan setinggi 132 meter lambang kemerdekaan Republik Indonesia dengan museum sejarah nasional dan dek observasi kota metropolitan.',
                'price' => 15000,
                'latitude' => -6.175392,
                'longitude' => 106.827153,
                'google_rating' => 4.6,
                'review_count' => 89100,
                'image' => 'https://images.unsplash.com/photo-1555899434-94d1368aa7af?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Sulawesi Utara',
                'name' => 'Taman Nasional Bunaken',
                'category' => 'Bahari',
                'description' => 'Surga bawah laut dengan keanekaragaman biota laut tertinggi, terumbu karang vertikal spektakuler, dan spot diving kelas internasional.',
                'price' => 50000,
                'latitude' => 1.621389,
                'longitude' => 124.764444,
                'google_rating' => 4.7,
                'review_count' => 11200,
                'image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'province' => 'Kepulauan Bangka Belitung',
                'name' => 'Pantai Tanjung Tinggi',
                'category' => 'Bahari',
                'description' => 'Pantai berpasir putih halus dengan susunan batu granit purba berukuran raksasa dan air laut tenang sebening kaca khas film Laskar Pelangi.',
                'price' => 10000,
                'latitude' => -2.571389,
                'longitude' => 107.671389,
                'google_rating' => 4.8,
                'review_count' => 16500,
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80',
            ],
        ];

        foreach ($destinations as $d) {
            $province = Province::where('name', $d['province'])->first();
            if ($province) {
                Destination::updateOrCreate(
                    ['name' => $d['name']],
                    [
                        'province_id' => $province->id,
                        'slug' => Str::slug($d['name']),
                        'category' => $d['category'],
                        'description' => $d['description'],
                        'price' => $d['price'],
                        'latitude' => $d['latitude'],
                        'longitude' => $d['longitude'],
                        'google_rating' => $d['google_rating'],
                        'review_count' => $d['review_count'],
                        'image' => $d['image'],
                    ]
                );
            }
        }
    }
}
