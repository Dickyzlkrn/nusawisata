<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds for all 38 Indonesian provinces.
     */
    public function run(): void
    {
        $provinces = [
            [
                'name' => 'Bali',
                'description' => 'Pulau Dewata yang terkenal dengan keindahan pantai, budaya luhur, dan pura bersejarah.',
                'image' => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'DI Yogyakarta',
                'description' => 'Kota budaya dan pelajar dengan warisan kraton, candi megah, dan kearifan lokal istimewa.',
                'image' => 'https://images.unsplash.com/photo-1596402184320-417e7178b2cd?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Jawa Timur',
                'description' => 'Kaya akan pesona gunung api ikonik seperti Bromo dan Ijen, serta pesisir pantai eksotis.',
                'image' => 'https://images.unsplash.com/photo-1588668214407-6ea9a6d8c272?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Jawa Barat',
                'description' => 'Bentang alam pegunungan sejuk, perkebunan teh yang asri, dan kuliner khas Sunda.',
                'image' => 'https://images.unsplash.com/photo-1601000938259-9e92002320b2?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Jawa Tengah',
                'description' => 'Jantung peradaban Jawa kuno dengan Candi Borobudur dan pesona dataran tinggi Dieng.',
                'image' => 'https://images.unsplash.com/photo-1596402184320-417e7178b2cd?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'DKI Jakarta',
                'description' => 'Metropolis dinamis dengan monumen bersejarah, museum modern, dan pusat gaya hidup urban.',
                'image' => 'https://images.unsplash.com/photo-1555899434-94d1368aa7af?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Nusa Tenggara Timur',
                'description' => 'Habitat kadal purba Komodo, surga selam Labuan Bajo, dan danau tiga warna Kelimutu.',
                'image' => 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Nusa Tenggara Barat',
                'description' => 'Kemegahan Gunung Rinjani, pantai berpasir pink, dan keindahan pulau gili eksotis.',
                'image' => 'https://images.unsplash.com/photo-1570789210967-2cac24afeb00?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Papua Barat Daya',
                'description' => 'Gerbang utama kepulauan Raja Ampat, surga keanekaragaman hayati laut dunia.',
                'image' => 'https://images.unsplash.com/photo-1516690561799-46d8f74f9abf?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Sumatera Utara',
                'description' => 'Danau vulkanik Toba nan megah, Pulau Samosir, dan ekowisata orangutan Bukit Lawang.',
                'image' => 'https://images.unsplash.com/photo-1563298723-dcfebaa392e3?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Sumatera Barat',
                'description' => 'Ngarai Sianok, Lembah Harau, Jam Gadang, dan surga kuliner rendang legendaris.',
                'image' => 'https://images.unsplash.com/photo-1568084680786-a84f91d1153c?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Sulawesi Utara',
                'description' => 'Taman Nasional Bunaken dengan dinding terumbu karang vertikal spektakuler.',
                'image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Sulawesi Selatan',
                'description' => 'Ritual adat mistis Tana Toraja, pantai Losari Makassar, dan karst Rammang-Rammang.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Sulawesi Tenggara',
                'description' => 'Taman Nasional Wakatobi, surga bawah laut kelas dunia di segitiga karang.',
                'image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Kalimantan Timur',
                'description' => 'Kepulauan Derawan dengan ubur-ubur tanpa sengat dan kawasan Ibu Kota Nusantara.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Kepulauan Bangka Belitung',
                'description' => 'Formasi bebatuan granit raksasa di tepi pantai berpasir putih kristal Negeri Laskar Pelangi.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Aceh',
                'description' => 'Serambi Mekkah dengan Masjid Raya Baiturrahman dan titik nol kilometer Pulau Weh.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Riau',
                'description' => 'Pusat kebudayaan Melayu dan Istana Siak Sri Indrapura.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Kepulauan Riau',
                'description' => 'Gugusan pulau wisata bahari Bintan, Batam, dan Anambas.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Jambi',
                'description' => 'Kompleks percandian Muaro Jambi terluas di Asia Tenggara dan Gunung Kerinci.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Bengkulu',
                'description' => 'Habitat bunga raksasa Rafflesia arnoldii dan Benteng Marlborough peninggalan Inggris.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Sumatera Selatan',
                'description' => 'Jembatan Ampera Palembang, Sungai Musi, dan kemasyhuran pempek khas.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Lampung',
                'description' => 'Taman Nasional Way Kambas konservasi gajah dan spot surfing Teluk Kiluan.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Banten',
                'description' => 'Taman Nasional Ujung Kulon suaka badak bercula satu dan kearifan Suku Baduy.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Kalimantan Barat',
                'description' => 'Kota Khatulistiwa Pontianak dan pesona alam Sungai Kapuas.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Kalimantan Tengah',
                'description' => 'Taman Nasional Tanjung Puting pusat konservasi orangutan terbesar di dunia.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Kalimantan Selatan',
                'description' => 'Pasar Terapung Lok Baintan yang legendaris dan pegunungan Meratus.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Kalimantan Utara',
                'description' => 'Keindahan hutan tropis Kayan Mentarang di perbatasan utara nusantara.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Gorontalo',
                'description' => 'Pesona hiu paus di Botubarani dan benteng peninggalan Otanaha.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Sulawesi Tengah',
                'description' => 'Kepulauan Togean yang perawan dan patung megalitikum Lembah Bada.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Sulawesi Barat',
                'description' => 'Negeri perahu sandeq Mandar dan pantai Manakarra.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Maluku',
                'description' => 'Kepulauan Rempah Banda Neira dengan sejarah benteng dan laut jernih.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Maluku Utara',
                'description' => 'Kesultanan Ternate & Tidore berlatar belakang gunung Gamalama.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Papua',
                'description' => 'Danau Sentani yang menawan dan puncak Pegunungan Jayawijaya.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Papua Barat',
                'description' => 'Teluk Bintuni dan keindahan alam Teluk Triton yang eksotis.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Papua Selatan',
                'description' => 'Kota Rusa Merauke dan Taman Nasional Wasur titik paling timur Indonesia.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Papua Tengah',
                'description' => 'Puncak Carstensz Pyramid dan keindahan Taman Nasional Lorentz.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Papua Pegunungan',
                'description' => 'Lembah Baliem Wamena dengan festival budaya tradisional suku Dani.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
            ],
        ];

        foreach ($provinces as $p) {
            Province::updateOrCreate(
                ['name' => $p['name']],
                [
                    'slug' => Str::slug($p['name']),
                    'description' => $p['description'],
                    'image' => $p['image'],
                ]
            );
        }
    }
}
