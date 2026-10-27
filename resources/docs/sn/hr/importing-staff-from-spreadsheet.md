---
title: Pinza vashandi kubva paspredishiti
summary: Dhawunirodha template yevashandi, izadze, uye ikwire - kurodha zvakare nema ID emunyika mamwe chete anogadziridza vashandi varipo pane kuvadzokorora.
category: hr
slug: importing-staff-from-spreadsheet
order: 2
published: true
updated: 2026-10-27
---

# Pinza vashandi kubva paspredishiti

Mutengesi anogadzira marekodhi evashandi muhuwandu, ane minda yakafanana ne **Mushandi Mutsva** wizard. Izvo zvakare zvakachengeteka kumhanya kanopfuura kamwe: vashandi vanofananidzwa ne **National ID**, saka re-uploader inogadziridza vashandi varipo pane kugadzira zvakapetwa.

## 1. Dhawunirodha template

1. Vhura **HR & Payroll → Staff Directory & HR → Vashandi**.
2. Dzvanya **Import Vashandi (Excel/CSV)**.
3. Mune wizard, tinya **Dhawunirodha Excel template**.

Iyo template inosvika neiyo chaiyo makoramu misoro uye mishanu mienzaniso mitsetse inoratidza kuti dzakasiyana data inofanira kutaridzika sei. Bvisa mienzaniso usati wapinda mudonzvo wako chaiwo, kana kuti uichengete uye ugadzirise maitiro.

## 2. Izadze mukati

Aya makoramu matanhatu chete ndiwo anofanirwa kunge aripo aine zvakakosha:

| Inodiwa column | Notes |
| --- | --- |
| Zita rokutanga | |
| Last Name | |
| Email | Inofanirwa kuve yakasarudzika kune vese vashandi uye portal account |
| Phone Number | |
| National ID | Kiyi yekufananidza - usamboichinja kune munhu aripo |
| Designation | Zita rebasa, e.g. "Mudzidzisi weMasvomhu" |

Zvimwe zvese zvine chekuita nesarudzo uye zvine hungwaru default:

| Column | Default kana isina chinhu |
| --- | --- |
| Gender | Mukadzi |
| Zuva rekuzvarwa | 1990-01-01 |
| Marital Status | Single |
| Nzvimbo Yenzvimbo | Not Provided |
| Emergency Contact Zita / Runhare | Not Provided |
| Department | Hapana chinhu (ona Madhipatimendi pazasi) |
| Basa | Teaching Staff |
| Employment Type | Permanent |
| Date Joined | Nhasi |
| Salary Giredhi | Hapana chinhu |
| Status | Active |

**Zuva Rokupera KweKondirakiti rinodiwa kana Rudzi rweBasa iri Kondirakiti.** Mazuva anofanira kunge ari eExcel date kana kuti `YYYY-MM-DD`.

### Mazita uye vaviri vaviri

Mamwe makoramu ane kukosha kunodarika imwechete. Shandisa colon pakati pezita uye kukosha, uye semicolon pakati pezvinyorwa:

- `Individual Allowances` → `Teaching Allowance: 150; Transport: 80`
- `Spouse Details` → `Spouse: Rudo Maphosa; Occupation: Nurse`
- `Dependents` → `Child 1: Tadiwa (8); Child 2: Kuda (5)`
- `Next of Kin` → `Name: Rudo Maphosa; Phone: 0771234567; Relationship: Spouse`
- `Custom Fields` → `TSC Number: 123456; Pension Number: PN-88`

## 3. Isa

1. Dzvanya **Import Vashandisi (Excel/CSV)** uye sarudza faira rakapedzwa. Makoramu anofananidzwa otomatiki kubva kumazita emusoro - hapana chidzitiro chemepu.
2. Ongorora pfupiso uye simbisa iyo yatorwa.

## 4. Chii chinoitika pakurodhazve

Ichi ndicho chikamu chakakosha chekuvandudza vashandi muhuwandu:

- **Same National ID, yakachinja ruzivo** → mushandi aripo anogadziridzwa. Hapana rekodhi nyowani, hapana yakapetwa portal account, uye hapana yechipiri activation email.
- **Same National ID ine email nyowani** → iyo email pane rekodhi yevashandi uye yavo yakabatana account account inogadziridzwa.
- **Itsva National ID** → mushandi mutsva anogadzirwa uye portal account inopihwa.
- **Email ndeyemumwe munhu** → mutsara unorambwa neshoko rakajeka; gadzirisa iyo email uye woisa zvakare.

Saka kuti uzorodze rondedzero yevashandi, dhawunirodha template (kana yako yekupedzisira kutumira kunze), chinja chete maemail kana mamwe mameseji aunoda, uye rodha iro faira zvakare.

## Madhipatimendi uye mihoro mamakisi

- **Dhipatimendi** rinoenderana nedhipatimendi riripo nemazita. Kana iyo isipo, mutengesi anobvunza kana kuigadzira kana kusvetuka iyo kukosha - kuigadzira inoburitsawo kodhi yedhipatimendi.
- **Muhoro giredhi** rinoenderana negiredhi riripo nemazita. Chisiya pasina kana munhu wacho asati awana giredhi.

## Kana mitsetse yakundikana

Kupinza hakumbochengete nehafu: mitsara ine matambudziko inorongwa mumibairo ine chikonzero chaicho (semuenzaniso musipo Contract End Date, munhukadzi asiri iwo, basa risingazivikanwe, kana zuva rakakanganisika). Dhawunirodha mitsetse yarambwa seCSV, gadzirisa, uye pinza faira iroro chete. Mitsara inoshanda mune imwe chete upload ichiri kutengwa kunze kwenyika.

> Chengetedza iyo National ID yakagadzikana kune vashandi varipo - iyo koramu imwe chete ndiyo inoita kuti idzore zvakare yekuvandudza kwete kuita kaviri.
