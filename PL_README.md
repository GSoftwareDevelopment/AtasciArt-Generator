Plik konfiguracyjny to standardowy plik JSON.
Jego pierwszy poziom, definiuje właściwości szablonu takie jak:

- `width`, `height` - szerokość i wysokość całkowita w znakach. **Te atrybuty są wymagne!**
- `colors` - tablica reprezentująca ustawienia kolorów (wartości dla rejestrów od 708 do 712)
- `encodeAs` - sposób kodowania linii wyników
- `scoreList` - tablica obiektów opisująca wygląd poszczególnych linii wyników. **Ten atrybut jest wymagany.**
- `screenFile`\* - plik binarny z zawartością ekranu
- `screenData`\* - tablica ciągów tekstowych opisująca zawartość ekranu (dane heksadecymalne)

_\* Jeden z dwóch atrybutów musi być określony w pliku._

Istotną kwestią jest rozpatrywanie atrybutów `screenFile` i `screenData`. W pierwszej kolejności brany jest pod uwagę atrybut `screenData`, a jeżeli nie jest określony to pobierane są dane z pliku opisanego w `screenFile`. Jeżeli są zdefiniowane oba atrybuty, priorytet będzie miał atrybut `screenData`!

# Opis listy wyników. Tablica objektów `scoreList`

Każda linia wyniku opisana jest osobnym obiektem.
W obiekcie wymagane są następujące atrybuty:

- `x` i `y` - określające początkowe położenie linii wyniku.
- `width` - szerokość linii wyniku.

Do opisu zawartości linii wyniku stosuje się opcje:

- `place` - generuje miejsce.
- `nick` - generuje nazwę gracza (jego nick)
- `score` - generuje osiągnięty wynik.
- `date` - generuje datę rejestracji wyniku.

Specjalną opcją jest atrybut `inversLine`, który ustawiony na `true` generuje tekst w odwróconych kolorach (operacja XOR na 7 bicie każdego bajtu linii)

Powyższe opcje to obiekty i każdy z nich MUSI mieć zawarte atrybuty:

- `shift` - przesunięcie względem początku linii (nadrzędne atrybuty położenia)
- `width` - szerokość generowanej wartości (w znakach)

Opcjonalnie, każda z opcji może posiadać atrybuty:

- `align` - justowanie wartości względem podanej szerokości obiektu (atrybut `width`) Możliwe wartości to: `left`, `center`, `right`. Wartość `right` jest domyślna.
- `fillChar` - znak, jakim będzie wypełniony obiekt na całej jego szerokości. Domyślną wartością jest znak #32 (spacja)
- `uppercase` - (ustawiony na wartość `true`) konwertuje znaki alfabetu na wielkie. Domyślnie ustawiony na `false`
- `lowercase` - (ustawiony na wartość `true`) konwertuje znaki alfabetu na małe. Domyślnie ustawiony na `false`
- `limitChars` - zawiera zestaw znaków, jaki jest akceptowany przy wyświetlaniu. Jego opis to wartość typu string, zawierająca wszystkie chciane znaki. W parze z tym atrybutem jest atrybut `replaceOutsideChars`. Domyślnie akceptowane są wszystkie znaki.
- `replaceOutsideChars` - ten atrybut określa znak, jaki będzie wstawiany w przypadku, gdy znak obiektu nie należy do zakresu określnego w atrybucie `limitChars`. Domyślną wartością jest #32 (spacja)

# Dodatkowe atrybuty opcji

Opcje `score` i `date` posiadają dodatkowe atrybuty, które rozszerzają interpretację wartości.

## Atrybuty dla opcji `score`

Opcja wyniku domyślnie interpretowana jest, jako wartość 32-bitowa typu całkowitego (przedstawiająca wynik punktowy osiągnięty przez gracza), ale może być interpretowana też jako czas.

Czas zapisywany jest w postaci liczby całkowitej zawierający część ułamkową, której dokładność określa atrybut `precision`. Jednak precyzja nie może być większa niż 1/100.

Aby przekształcić wynik w format czasu należy zdefiniować następujące atrybuty w opcji `score`:

- `showScoreAs` - wartość tego atrybutu określ jako `time`
- `precision` - określ dokładność z jaką będzie interpretowana wartość wyniku (1/n części sekundy)
- `formatTime` - opisz format który będzie zastosowany w wyniku

`formatTime` jest ciągiem znaków, który opisuje jakie części czasu będą wyświetlane. Znaczenie znaków w tym ciągu jest następująca:

- `h` - ilość godzin (bez zera wiodącego)
- `Hn` - ilość godzin, gdzie n określa ilość zer wiodących
- `m` - ilość minut (z zerem wiodącym)
- `s` - ilość sekund (z zerem wiodącym)
- `f` - część ułamkowa sekundy
- `Fn` - j.w. tylko n określa ilość miejsc po przecinku.

## Atrybuty dla opcji `date`

Atrybutem rozszerzającym opcje `date` jest `formatDate`. Jest to ciąg znaków opisujących sposób, w jaki ma być interpretowana data powstania wyniku. Domyślnie stosowany jest format `Y.m.d`

Funkcją formatującą czas jest funkcja języka PHP `date()`. Jej opis znajdziesz [tu](https://www.php.net/manual/en/function.date.php), a możliwe opcje formatowania [tu](https://www.php.net/manual/en/datetime.format.php).
