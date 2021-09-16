<?php

namespace Core\Command;

use Admin\modules\WithdrawalDisabledUsers;
use Core\Blockchain\Factory;
use Db\Transaction;
use Db\Where;
use Models\AddressModel;
use Models\BalanceHistoryModel;
use Models\BalanceModel;
use Models\BannedUserModel;
use Models\DepositModel;
use Models\InternalTransactionModel;
use Models\PaymentModel;
use Models\ProfitModel;
use Models\UserModel;
use Models\WalletModel;
use Models\WithdrawDisabledModel;
use Modules\BalanceModule;
use Modules\InvestmentModule;
use Modules\WalletModule;

class Goglev implements CommandInterface {
    private $name;
    private $params;

    function __construct(string $name, array $params) {
        $this->name = $name;
        $this->params = $params;
    }

    public function exec() {
        echo $this->name . PHP_EOL;
        print_r($this->params);
        switch ($this->name) {

            case 'ban_token_users':
                exit;
                $ids = [2721,1291,834,836,236,2652,252,364,1123,3194,1211,587,2796,3140,2060,504,525,1762,431,1264,39,200,1392,535,1798,1755,393,138,251,735,191,3927,497,652,1692,427,531,46,626,717,110,1414,550,2885,829,839,1067,578,367,1005,591,719,1758,1496,2812,574,569,555,463,2931,721,88,218,106,368,891,1709,503,772,477,983,1698,1258,184,672,751,593,2063,1733,429,797,744,379,1178,1071,52,1144,1827,3590,826,529,468,7,2501,1113,850,86,937,1804,1713,960,3172,370,1750,458,1210,782,514,1427,787,433,137,1761,1080,544,902,692,72,2497,155,995,1185,2986,730,377,1014,2172,2073,1663,455,1406,650,1081,1459,930,561,2312,668,1682,1752,371,1768,1365,486,1151,1161,543,3486,789,1917,1253,1107,1318,1673,3010,1094,202,625,3374,1200,1162,547,182,872,508,734,500,203,750,1309,629,592,472,607,369,115,3026,152,2747,1193,484,728,1805,1326,768,185,747,2138,1825,1832,964,2358,1189,696,1277,2003,1143,3144,476,397,711,638,774,892,231,790,752,2634,247,559,461,3502,3928,1934,2540,3087,1900,14,506,2105,225,408,972,656,854,1760,3751,409,462,1026,1117,1245,493,1543,391,792,1054,314,3952,2875,794,639,2662,392,1184,580,678,435,2723,64,632,1440,604,424,1228,2650,970];
                $banned = WithdrawDisabledModel::select(Where::and()
                    ->set('user_id', Where::OperatorIN, $ids)
                );

                $banned_map = [];
                foreach ($banned as $row) {
                    /* @var WithdrawDisabledModel $row */
                    $banned_map[$row->user_id] = $row;
                }

                foreach ($ids as $uid) {
                    if (!isset($banned_map[$uid])) {
                        $b = new WithdrawDisabledModel();
                        $b->user_id = $uid;
                        $b->banner_id = ID_NRADIONOV;
                        $b->reason = 'Banned due to disabling investments';
                        $b->save();
                    }
                }

                break;

            case 'transfer_invest_profit_to_wallet':
                exit;
                $wallets = WalletModel::select(Where::and()
                    ->set('profit', Where::OperatorGreater, 0)
                    ->set('user_id', Where::OperatorIN, [1291,834,836,236,2652,252,364,1123,3194,1211,587,2796,3140,2060,504,525,1762,431,1264,39,200,1392,535,1798,1755,393,138,251,735,191,3927,497,652,1692,427,531,46,626,717,110,1414,550,2885,829,839,1067,578,367,1005,591,719,1758,1496,2812,574,569,555,463,2931,721,88,218,106,368,891,1709,503,772,477,983,1698,1258,184,672,751,593,2063,1733,429,797,744,379,1178,1071,52,1144,1827,3590,826,529,468,7,2501,1113,850,86,937,1804,1713,960,3172,370,1750,458,1210,782,514,1427,787,433,137,1761,1080,544,902,692,72,2497,155,995,1185,2986,730,377,1014,2172,2073,1663,455,1406,650,1081,1459,930,561,2312,668,1682,1752,371,1768,1365,486,1151,1161,543,3486,789,1917,1253,1107,1318,1673,3010,1094,202,625,3374,1200,1162,547,182,872,508,734,500,203,750,1309,629,592,472,607,369,115,3026,152,2747,1193,484,728,1805,1326,768,185,747,2138,1825,1832,964,2358,1189,696,1277,2003,1143,3144,476,397,711,638,774,892,231,790,752,2634,247,559,461,3502,3928,1934,2540,3087,1900,14,506,2105,225,408,972,656,854,1760,3751,409,462,1026,1117,1245,493,1543,391,792,1054,314,3952,2875,794,639,2662,392,1184,580,678,435,2723,64,632,1440,604,424,1228,2650,970])
                    ->set('currency', Where::OperatorIN, [CURRENCY_BTC, CURRENCY_ETH, CURRENCY_LTC])
                );

                Transaction::wrap(function () use ($wallets) {
                    foreach ($wallets as $wallet) {
                        /* @var WalletModel $wallet */

                        $amount = $wallet->profit;

                        if (!$wallet->subProfit($amount)) {
                            throw new \Exception();
                        }

                        if (!$wallet->addAmount($amount)) {
                            throw new \Exception();
                        }

                        $payment = new PaymentModel();
                        $payment->wallet_id = $wallet->id;
                        $payment->amount = $amount;
                        $payment->created_at = date('Y-m-d H:i:s');
                        $payment->user_id = $wallet->user_id;
                        $payment->status = 'accepted';
                        $payment->wallet_address = $wallet->address;
                        $payment->save();
                    }
                });
                break;

            case 'transfer_assets_from_exchange':
                exit;
                $balances = BalanceModel::select(Where::and()
                    ->set('category', Where::OperatorEq, BalanceModel::CATEGORY_EXCHANGE)
                    ->set('amount', Where::OperatorGreater, 0)
                    ->set('currency', Where::OperatorIN, [CURRENCY_BTC, CURRENCY_ETH, CURRENCY_LTC])
                );

                foreach ($balances as $balance) {
                    /* @var BalanceModel $balance */

                    $wallet = WalletModule::getWallet($balance->user_id, $balance->currency);
                    Transaction::wrap(function () use ($balance, $wallet) {

                        $amount = $balance->amount;
                        if (!$balance->decrAmount($amount)) {
                            throw new \Exception();
                        }

                        if (!$wallet->addAmount($amount)) {
                            throw new \Exception();
                        }

                        $transaction = new InternalTransactionModel();
                        $transaction->currency = $balance->currency;
                        $transaction->amount = $amount;
                        $transaction->from_category = InternalTransactionModel::CATEGORY_EXCHANGE;
                        $transaction->to_category = InternalTransactionModel::CATEGORY_WALLET;
                        $transaction->setFrom($balance);
                        $transaction->setTo($wallet);
                        $transaction->save();
                    });
                }

                break;

            case 'map_emails_to_ids':
                $emails = <<<TEXT
mogushkov1985@mail.ru
jonn000jonn@gmail.com
ydan77284@gmail.com
ydonier@gmail.com
gector1@protonmail.com
gector1995@gmail.com
orionw@protonmail.com
w2125224@icloud.com
artak.sakapetoian@gmail.com
fahribatubara@yahoo.com
grushetskaya.atv@gmail.com
hendrikuj83@gmail.com
m.k.omarov@ya.ru
bitbotpool@gmail.com
bogdanbradov@gmail.com
Cooller707@gmail.com
marsio22@yahoo.com
Nasim.m2019a@gmail.com
Nikbiin@yahoo.com
Stan.sadovnikov@gmail.com
yusss94@gmail.com
yusss.94@gmail.com
d.a.erohin@protonmail.com
k1030jd@gmail.com
d.a.erohin@gmail.com
vip.abrarov@mail.ru
vip.abrarov@gmail.com
Hushang.gh@icloud.com
sohrobs@gmail.com
hushang12@gmail.com
TEXT;

                $emails = explode(PHP_EOL, $emails);
                $users = UserModel::select(Where::and()
                    ->set('email', Where::OperatorIN, $emails)
                );

                $ids = [];
                foreach ($users as $user) {
                    $ids[] = $user->id;
                }

                print_r(implode(',', $ids));
                break;

            case 'stop_deposits':
                exit;
                $users = [];

                $deposits = DepositModel::select(Where::and()
                    ->set('user_id', Where::OperatorIN, $users)
                    ->set('status', Where::OperatorEq, DepositModel::STATUS_ACCEPTED)
                );

                foreach ($deposits as $deposit) {
                    /* @var DepositModel $deposit */

                    $profits = ProfitModel::select(Where::and()
                        ->set(Where::equal('deposit_id', $deposit->id))
                        ->set('created_at_timestamp', Where::OperatorGreaterEq, strtotime('21.10.2019'))
                        //->set('created_at_timestamp', Where::OperatorLowerEq, strtotime('20.07.2020'))
                    );

                    if (!$profits->count()) {
                        continue;
                    }

                    Transaction::wrap(function () use ($profits, $deposit) {

                        $profits_sum = 0;
                        foreach ($profits as $profit) {
                            $profits_sum += $profit->amount;
                        }

                        $deposit->days -= $profits->count();
                        if ($deposit->dynamic_percent != 0) {
                            $deposit->dynamic_profit -= $profits_sum;

                            if ($deposit->withdraw_disabled == 0) {
                                $deposit->dynamic_profit_share -= $profits_sum;
                            }

                            if ($deposit->dynamic_percent == 2) {
                                $deposit->dynamic_curr_percent -= $deposit->dynamic_daily_percent * $profits->count();
                            }
                        }

                        $deposit->status = DepositModel::STATUS_DONE;

                        $wallet = WalletModule::getWallet($deposit->user_id, $deposit->currency);
                        $wallet->subProfit($profits_sum);

                        $profit = new ProfitModel();
                        $profit->deposit_id = $deposit->id;
                        $profit->type = 'sub_profit';
                        $profit->user_id = $deposit->user_id;
                        $profit->amount = -$profits_sum;
                        $profit->wallet_id = $wallet->id;
                        $profit->target_id = 0;
                        $profit->currency = $deposit->currency;
                        $profit->created_at = date('Y-m-d H:i:s');
                        $profit->save();

                        $deposit->save();

                        print_r([
                            'user_id' => $deposit->user_id,
                            'deposit_id' => $deposit->id,
                            'amount' => $profits_sum,
                        ]);
                    });
                }
                break;

            case 'ban_users':
                $emails = <<<TEXT
sasha_kormakov@mail.ru
behroz25@mail.ru
polbear90@gmail.com
GALINAMOIS61@gmail.com
ozi_kzn@mail.ru
zorikto2@gmail.com
kal-Ilnaz@yandex.ru
gorari@mail.ru
larsislarsis40@gmail.com
marat9008@mail.ru
misha_minin1988@mail.ru
buh_nvem@mail.ru
natasharukhlina@gmail.com
bondareva_ov@mail.ru
Islam.s1995@mail.ru
slezkinvs@gmail.com
yorlova77@yandex.ru
abraham.chuljyan98@gmail.com
lexa40rus.1997@mail.ru
migalkina1@gmail.com
andrevictorevers@gmail.com
annasepeeva@icloud.com
annu@mail.ru
arutyun@nazaryan.org
Fonddenisbrz@gmail.com
Dimplantsale@gmail.com
gor19973010@gmail.com
newopost@gmail.com
hayklife@yahoo.com
igor.bezrukov.1993@mail.ru 
kingslayersocial@gmail.com
kolyato@protonmail.com
maxmaxov6669999@gmail.com
Melikyanlara999@gmail.com
Lernik_999@mail.ru
shalovskayaln@gmail.com
myartmyjob@gmail.com
m.agafonov@tozachay.ru
mirakurmanina@gmail.com
oleg@emby.ru
fitmomtatiana@gmail.com
digitronic@list.ru 
Lyudmila-dering@mail.ru
Yulia.bodyquest@gmail.com
a.paradoks@email.ru
7844027@gmail.com
zantonix@gmail.com
077555558@mail.ru
3377160@mail.ru
Aleeexkad@gmail.com
alenushka3@list.ru
alesyamo595@gmail.com
alex.cherepanov1989@gmail.com
alpi1406@gmail.com
andrei_duhovskoi@mail.ru
anna.gz.rus@gmail.com
anna.smorkalova.2018@gmail.com
antropova1741@gmail.com
astarov88888@gmail.com
at636@mail.ru
balicryptoshake@gmail.com
battalowa.r@yandex.ru
buter007@tut.by
cargo2828@mail.ru
cheese132015@gmail.com
christina.kirael@gmail.com
ddemiddov11@gmail.com
denboss79@mail.ru
dskwer@mail.ru
dstarova39@gmail.com
efremovalexander38@gmail.com
evgeniia.arutiunova@gmail.com
frez66@gmail.com
gasparyan_tv@ena.am
gviniashviliiz@yandex.ru
hadjiandrey@icloud.com
hakimov.kirill.99@gmail.com
hiddenvasya@gmail.com
hovooutbox@inbox.ru
investcapital1989@gmail.com
ivan.pushchaev@gmail.com
jasmin.yaqubi2001@gmail.com
joyka08@mail.ru
kidaxa@gmail.com
L.if84@mail.ru
maltsevva@yandex.ru
mapetstar@gmail.com
max270296@gmail.com
mihpop595@gmail.com
mr.ivanlogunov@mail.ru
n696nc@gmail.com
olexgu@gmail.com
paradise8882@mail.ru
pavelwhitewell@gmail.com
ps89890@icloud.com
ren18081992@gmail.com
rezanov161090@gmail.com
Sap75.75@mail.ru
severboyko82@mail.ru
svetlanka111888@mail.ru
Timurash9@gmail.com
triprimata@gmail.com
ubudlife30@gmail.com
ummvildan@gmail.com
valiv.a@mail.ru
vaskoz033@gmail.com
vital.mgn@mail.ru
yudaev.m.2018@gmail.com
zaitseva9090@gmail.com
TEXT;

                $users = UserModel::select(Where::in('email', explode(PHP_EOL, $emails)));
                foreach ($users as $user) {
                    /* @var UserModel $user */
                    if (!$user->isBanned()) {
                        Transaction::wrap(function() use ($user) {
                            $ban_user = new BannedUserModel();
                            $ban_user->reason = 'Banned by Nikita';
                            $ban_user->user_id = $user->id;
                            $ban_user->admin_id = ID_NRADIONOV;
                            $ban_user->save();
                            $user->ban_id = $ban_user->id;
                            $user->save();
                        });
                    }
                }

                echo 'OK'.PHP_EOL;
                break;


            case 'total_balances':

                $emails = <<<TEXT
dana_detsik@mail.ru
axmirova1714@gmail.com
giftedq3@mail.ru
polonnikov.maks001@gmail.com
nik.kiselev.92@list.ru
vegascity@mail.ru
antoha0070072@mail.ru
Artjom.polanski@gmail.com
dmitriytikachev@gmail.com
ekaterina88maksimova@gmail.com
magner1@protonmail.com
guygame000yt@gmail.com
andrey.bakhaev1@gmail.com
andrey93vorobey@icloud.com
bezoo@inbox.ru
gjegash80@gmail.com
vital.mgn@gmail.com
yarstsevroman@gmail.com
avkm2008@gmail.com
Maksimanastasievna@gmail.com
telyatiev86invest@gmail.com
murashko99999@gmail.com
hatiko1962@gmail.com
Alexrz6214@gmail.com
gladnikovaleksandr@gmail.com
alexandanna2018bali@gmail.com
alexander132587@gmail.com
ukladhik_83@mail.ru
chuba_82@mail.ru
avshikhalev@gmail.com
ecoco_microzelen@mail.ru
alexey.muse@mail.ru
mmmalexey13@gmail.com
rv45@list.ru
maa9044135650@gmail.com
arzamon@yandex.ru
dav603.club@gmail.com
aychern9@gmail.com
btcbel@mail.ru
gandrey76@gmail.com
likbb@mail.ru
ki_anka@mail.ru
Gggmmm@mail.ru
9093858858@mail.ru
bitbot134@gmail.com
badma.radnaev1@gmail.com
bmelcov@yandex.ru
deejeeno@gmail.com
arbuzius@bk.ru
vadyuha.sl@gmail.com
Tsapko.ViV@gmail.com
rvener034@gmail.com
vera16111986@gmail.com
dubric33333333@gmail.com
pvpmillioner@gmail.com
victoriyab.job@gmail.com
kucherskyivitalii@gmail.com
treidervlamas50@gmail.com
vlad16bitcoin@gmail.com
Vladislavtsuplacov@gmail.com
vyacheslav.alexeev@gmail.com
9999953333@mail.ru
j0efest@mail.ru
Rabota201508@mail.ru
denisinvestor398@gmail.com
densky435@gmail.com
dkarpiki@gmail.com
chinkov62@mail.ru
dimay1y2y3y@gmail.com
datokvantry@gmail.com
dmitry.ya888@gmail.com
meizuphonepro@gmail.com
9tempest9@gmail.com
evmerc852@gmail.com
ya.jennim@ya.ru
evkan201@gmail.com
egv9608833993@gmail.com
zekaterina132@gmail.com
ioanngor13@gmail.com
4eloveksam@protonmail.com
zakirovilnaz@bk.ru
ilshat332@mail.ru
zhivl333@gmail.com
kok81@mail.ru
komarovk151@gmail.com
baeksenia1985@gmail.com
larainvestor@gmail.com
Fedotovaolga1408@gmail.com
Lianaogannisyan@mail.ru
R0manichP@yandex.ru
ludmilochka.5555@yandex.ru
mokoshev@gmail.com
localnew@mail.ru
maks.jokerjokerjoker.maksimov@mail.ru
Maksprimakov@icloud.com
maa9608708705@gmail.com
mariyakostyreva2019@gmail.com
ima9377491114@gmail.com
kirillpop001@gmail.com
bot_999@mail.ru
manymoney9@mail.ru
nikola_bolshakov@icloud.com
super.kolya5220@yandex.ru
ninvolga24@yandex.ru
chernichka0702@gmail.com
akulenko.petya3@yandex.ru
sstimool@gmail.com
lirusya0@gmail.com
tsapko.rs@gmail.com
7737562@mail.ru
sarowone@gmail.com
kallassssss@gmail.com
se_k@list.ru
sprokofevm@gmail.com
Ana1994timurka1995@mail.ru
fbarsukovista@gmail.com
hkdfgiuewj@gmail.com
suleiman13@mail.ru
rubcovau52@gmail.com
mainerparking@gmail.com
yury.peri@gmail.com
teresh.19831983@gmail.com
ducatistarus@gmail.com
virt.virt@mail.ru
yarvvin@gmail.com
Air_san@bk.ru
akobchobanyan@gmail.com
albert21704@gmail.com
asusf555y@gmail.com
novikmmx@gmail.com
1xxx1983@gmail.com
644643@protonmail.com
solnyshkoas@mail.ru
force.vision@yandex.ru
buanov414@gmail.com
alex.naumovich@gmail.com
jhonsonalex70@gmail.com
abcinvest11@gmail.com
VstrAlexey@gmail.com
Alina.chernoiwanowa@yandex.ru
eat116@mail.ru
jarinandrey@gmail.com
spas.andrey@gmail.com
pcuzer@gmail.com
annabella.artyushenko@gmail.com
ortkamchatka@gmail.com
anhar87@mail.ru
antonbannikov1981@gmail.com
anlyubimov@gmail.com
caarus29@gmail.com
armen.avdalyan.68@mail.ru
fakelmen1996@gmail.com
surrealboy@yandex.ru
ka50akaviva@gmail.com
mail@bestled.su
artyompoghosyan1978@mail.ru
sahapov116@gmail.com
dsprokofyev@gmail.com
drapeznica@mail.ru
agahanovdavid20@gmail.com
davit_mxitaryan@mail.ru
idenisderkach@gmail.com
sabo.dmitriy@gmail.com
zaytsev_dmi@mail.ru
dgromuko@gmail.com
nakonechniuk@gmail.com
sef.69@mail.ru
evdoshenkolena@gmail.com
elena.sabo555@gmail.com
bit.bot.19@bk.ru
Latar1396@gmail.com
tehpartner@bk.ru
Jenovik@gmail.com
rabota_prog@mail.ru
fanblockchain92@ya.ru
my_renaissance@mail.ru
inesaavanesyan@mail.ru
rytov.ga@gmail.com
gennady.wwp@ya.ru
gioagaxanov@gmail.com
agenda23rus@protonmail.com
haykhovhannisyan76@gmail.com
rupica888@gmail.com
sbsdf329798@protonmail.com
lapinigor77@gmail.com
111igor1993igor111@gmail.com
rudiak300@gmail.com
shumif@mail.ru
gorari37@gmail.com
ilichur@gmail.com
Araxos@mail.ru
z5003456@gmail.com
zen4enko.iw@yandex.ru
darkdevilcat@gmail.com
kristinearyumyan@mail.ru
senkoksenia@gmail.com
exchange710@gmail.com
baghdasaryan94@mail.ru
yourworstfriendmak@protonmail.ch
maxserg58@gmail.com
moonpromusic@gmail.com
mashabold@yandex.ru
karmazinamarina@gmail.com
soldatenko.mary@gmail.com
mkornienko58@gmail.com
mihran21@mail.ru
natalika76@list.ru
dara_eko@mail.ru
chilina@bk.ru
nnersesyan1979@gmail.com
kliman33@yandex.ru
alex.landiak@gmail.com
pavel0765@yandex.ru
Ppersik@protonmail.com
privetbali@gmail.com
palki899@gmail.com
Bureacrate@yahoo.com
d.duck.dev@gmail.com
telichko05@gmail.com
esenin2003@gmail.com
staszelenskiy@gmail.com
Mymail1987@mail.ru
vlsveta0@gmail.com
tatianakutenko@icloud.com
tgwharmony@gmail.com
tatyana.taraymovich@mail.ru
knopsit@gmail.com
lonelydiablo123@gmail.com
vmalkin95@gmail.com
Victory_loves@bk.ru
victor.inc@mail.ru
Kubrush@mail.ru
pestrislav@tuta.io
rodionovvlad94@yandex.ru
qzenod@gmail.com
canavaro18@yandex.ru
yarikkvz89@gmail.com
89208412702@mail.ru
yaznur01@gmail.com
yegorsamosenko@gmail.com
youzhe@yandex.ru
momentum.rub@gmail.com
yurikryuchkov982@gmail.com
9616330921y@gmail.com
p4kelzatjoul@mail.ru
pav-tsybko@yandex.ru
nataliabel1@mail.ru
cjartem@bk.ru
sergant451@mail.ru
89213606771s@mail.ru
89626231222@mail.ru
kirill.abdullin2014@yandex.ru
vasilsh05@gmail.com
il.bernikov@yandex.ru
yntunen.pavel@gmail.com
kolz0r@yandex.ru
sandra_york@bk.ru
pmikk@tutanota.com
TEXT;


                $token_emails = <<<TEXT
Alexbir87@mail.ru
Karandashwtf@yandex.ru
kriptolife2019@gmail.com
kai9033729830@gmail.com
AleksandrMokin82@gmail.com
mrsteelmr@gmail.com
florkila@mail.ru
raimiy97@gmail.com
alexeycibi@gmail.com
alima13@bk.ru
albert_gazeev@mail.ru
gdays7973@gmail.com
blockchaingordandrey@gmail.com
andrew.kuzankin@gmail.com
ahha-777@mail.ru
holodiloanna@gmail.com
annashapovalova063@gmail.com
aeletskaya@mail.ru
Revorgnat@gmail.com
ravager1989@yandex.ru
vadim_kleshchev@mail.ru
davidovvadim495@gmail.com
vikulya-ru@mail.ru
usmanovaaaaa@gmail.com
vitaliygizatulin@gmail.com
vitinvest19@gmail.com
vladimir.kolchik@gmail.com
pv9375442063@gmail.com
bitcoinbotkurdyukov@gmail.com
gmh9189198884@gmail.com
raccoon10025@gmail.com
dbugsenkk@gmail.com
panenko.denis@gmail.com
denistabinaev92@mail.ru
dmitriypro0807@mail.ru
perkov_dima@mail.ru
vremiaprazdnika@mail.ru
evyzhanov@yandex.ru
jenyazin@gmail.com
beetle841@gmail.com
dikidzhi1987@gmail.com
evgenia.kiseleva96@bk.ru
ekaterina.avaeva@gmail.com
kate.grin5@gmail.com
9616858538@mail.ru
khristenkoe@yandex.ru
karrdamon@gmail.com
elena_vasileva_64@inbox.ru
lerugzik@gmail.com
Travkin.91@bk.ru
9682848484@mail.ru
semenovailona1410@gmail.com
Nexus52517@icloud.com
shureguitar@gmail.com
Alsaheer_85@yahoo.com
viv9610614555@gmail.com
k.irina.e@inbox.ru
recrosas@gmail.com
investment1786@mail.ru
advcash2019@gmail.com
dr.k.konstantin@gmail.com
Varan01012008@gmail.com
kristinakuza98@gmail.com
kristina.aaa61@yandex.ru
Levklyushnikov@gmail.com
pln9047534512@gmail.com
alexinvestforex@gmail.com
yusova.97@bk.ru
mishaabramov.ru@mail.ru
mixailviktorowi4@gmail.com
mikhail.rich26@gmail.com
e-z34@mail.ru
sohum123777@gmail.com
natalyainvestgroup@gmail.com
nellichka1503@mail.ru
hks-jap@bk.ru
millioner2019jftu@gmail.com
ninaxray237@gmail.com
oshmelkova81@gmail.com
eks-promt@yandex.ru
ivan6558dhdhj@gmail.com
olgabrykhova@gmail.com
makueva.olga@yandex.ru
rod9377074477@gmail.com
Olgatavshanzhi@gmail.com
kupriyanov10b@gmail.com
smis.polina@yandex.ru
polik199666@gmail.com
megateremok@mail.ru
nlrv16@yandex.ru
fonctionne@icloud.com
Reg.Zarazka@gmail.com
Rimmel63@mail.ru
faritovr5@gmail.com
gr.roman-64@yandex.ru
Scienet@yandex.ru
m2zloba@gmail.com
sulzhinroman@gmail.com
spectrarer@mail.ru
franky_g@inbox.ru
golovanov1981sergei@gmail.com
sdavidovich@protonmail.com
Sdemanov2012@gmail.com
zhiglov8@gmail.com
koletoms@gmail.com
svkondr@mail.ru
spbsvl@mail.ru
sergey.lukyanov@protonmail.com
invgteams@gmail.com
ruthekla@protonmail.com
tonkova.tatyana@mail.ru
faxrixalilli@protonmail.com
goncharenko_elya@mail.ru
yuliarudaya111@gmail.com
romanukwoman@gmail.com
u-v-kayukova@mail.ru
uzhukov@me.com
yurayadrov@mail.ru
Radzhabov.albart@yandex.ru
533838nv@gmail.com
Alex.ant.1301@gmail.com
Alenastyreva@gmail.com
aimner@mail.ru
boss_em@mail.ru
agolshev@gmail.com
dasha009@yandex.ru
alex.guzcc@gmail.com
lichman.a@gmail.com
bassuga84@mail.ru
Alexandr.yakimenko.ua@gmail.com
serzh.invest@bk.ru
gaechka.nastia@gmail.com
nasty.zagumenova@gmail.com
petrina.anastasiya@mail.ru
pfvos38@gmail.com
Praktikant994@yandex.ru
kerchmart@gmail.com
alexandbestfamily@gmail.com
abapova@hotmail.com
meridianna@protonmail.com
nal87@yandex.ru
youcandoit1808@gmail.com
Newantonbannikov1981@gmail.Com
seejey@gmail.com
zeyn.m@mail.ru
voodik.am@gmail.com
armant73@yandex.ru
armenmar6@gmail.com
exchange710@rambler.ru
artyometum@gmail.com
artcoin789@gmail.com
kripta222@mail.ru
artem19636@gmail.com
a.g.zakiev@gmail.com
Cryptaexpert87@gmail.com
ultra_light@mail.ru
efideni2019@gmail.com
Denyaoos@mail.ru
mr.dimyxa@mail.ru
amid1410.batura@gmail.com
digitlibre@gmail.com
Print-online@inbox.ru
awsyak@gmail.com
Kate.shkurko@gmail.com
aksyonova2105@gmail.com
konfiti1994@gmail.com
khanelisaveta@gmail.com
Rus-oko@mail.ru
7dansistem@gmail.com
vahmurko@gmail.com
gremlin.012345@gmail.com
gagik.hovhannisyan@gmail.com
gmolokova04@gmail.com
arustamyan1981@list.ru
goldenaxe@protonmail.com
elaboration32@gmail.com
mik@imitra.ru
bulchenkov@gmail.com
Irina.vitalevna88@gmail.com
Bk.nil@yandex.ru
ivanderkach77@gmail.com
cytyz.rambler@gmail.com
semenov@mail.com
ojbinvesttomorrow@gmail.com
manjosova777@gmail.com
karina.lion.list@gmail.com
ekaterinayakovenko777@gmail.com
kris.love.jon@gmail.com
prostobush@ya.ru
Saratovkr@yandex.ru
brconstantin24@gmail.com
Tcarkova.Kristina@gmail.com
special.85@mail.ru
lpaderina@mail.ru
ooomakkut@gmail.com
maksymka@protonmail.com
dievaly@gmail.com
emelimari@icloud.com
marina.shalak2017@gmail.com
marinazhemchuzh@gmail.com
Borodkina_m@mail.ru
mrigorgoldmen@gmail.com
m.gvozduhin@icloud.com
mikakvartz@gmail.com
mkomariam@gmail.com
danilinannn@gmail.com
ngromyko@rambler.ru
nvr8@mail.ru
Ligezanika6@gmail.com
kolyasic1@gmail.com
Ninamatch@protonmail.com
secretoks03@gmail.com
permyakovoleg@gmail.com
butterflycrypt@gmail.com
leka88-09@inbox.ru
mikaelyan1932@mail.ru
oleg.4arter@gmail.com
davx44@gmail.com
nickjak5588212cash@gmail.com
admirk@yandex.ru
ruslan383@gmail.com
Rustam-islamov1984@yandex.ru
salainvest31@gmail.com
saqo0@protonmail.com
sygyn@yandex.ru
ser1ko93rus@mail.ru
smolokov14@gmail.com
serik.iab21@gmail.com
Smirniy9488@mail.ru
sofcreat@yahoo.com
cononenko-sveta@ya.ru
sv.platonova.bcn@gmail.com
raztumasyan@icloud.com
agahanovdavid101@mail.ru
bestatmov2019@gmail.com
fedorgromovworkingbox@gmail.com
badanovtimur@gmail.com
donchak83@protonmail.com
vidukaz.ru@mail.ru
valensiay@me.com
Valentina0703@inbox.ru
shevsl@ya.ru
victoriaguzenko@yandex.ru
Pogorelyvm@gmail.com
kozachenko.vitaliy@gmail.com
ray2019vk@gmail.com
5811188@gmail.com
valdemar7@protonmail.com
tratsevych@gmail.com
liza.vasylieva@gmail.com
ledixxl.2007@gmail.com
yuliakhristenko89@gmail.com
pawarker@gmail.com
d.yarmukhametov@yandex.ru
stripes_a-di-das@mail.ru
Torri2409@mail.ru
mashabolddesign@gmail.com
vasiliev198627@yandex.ru
romtimco@rambler.ru
olympicpro58@gmail.com
ebi.vosoghi@gmail.com
qasem.akbari@gmail.com
Jannik.steffen.evers@gmail.com
Info@vonmeissenstein.de
sgsm.viv@protonmail.com
billy_surf@icloud.com
hermzliu@gmail.com
TEXT;

                $emails = explode(PHP_EOL, $emails);

                $users = UserModel::select(Where::in('email', $emails));
                $wallets = WalletModel::select(Where::in('user_id', $users->column('id')));

                $total = [];
                foreach ($wallets as $wallet) {
                    /* @var WalletModel $wallet */
                    $total[$wallet->currency] += $wallet->amount + $wallet->profit;
                }


                print_r([
                    'total' => $total,
                ]);

                break;

            case 'total_tokens':
                $wallets = WalletModel::select();

                $total = [];
                foreach ($wallets as $wallet) {
                    /* @var WalletModel $wallet */
                    $total[$wallet->currency] += $wallet->amount + $wallet->profit;
                }


                print_r([
                    'total' => $total,
                ]);

                break;

            case 'test':
                $i = Factory::getInstance('btc');
                print_r($i->getTransactionInfo('a0d01ce6d9f657de4f7dc6d35b997e99380e04cb3b8f7e5ce9b6ec9b90f6db28'));
                break;
            case 'create_pool':
                $user_id = isset($this->params['user_id']) ? $this->params['user_id'] : false;
                $amount = isset($this->params['amount']) ? $this->params['amount'] : false;
                $currency = isset($this->params['currency']) ? $this->params['currency'] : 'btc';
                $date = isset($this->params['date']) ? $this->params['date'] : null;

                if (!$user_id) {
                    die("'user_id' is required");
                }

                if (!$amount || $amount < 0) {
                    die("'amount' is required");
                }

                InvestmentModule::createPoolDeposit($user_id, $amount, $currency, $date);


                echo 'OK';
                break;
            case 'pool_profit':
                exit;
                //$deposit_ids = isset($this->params['deposit_id']) ? $this->params['deposit_id'] : false;
                $deposit_ids = '3215,3094,3097,3098,3099,3095,3155,3156,3157,3158,3216,3217,3218,3219,3220,3221,3222,3272,3273,3324,3325,3295,3280,3286,3407,3406';

                // php Manage.php -c goglev --name pool_profit --params '{"percent":4,"date":"02.06.2020"}'

                $percent = isset($this->params['percent']) ? $this->params['percent'] : false;
                $date = isset($this->params['date']) ? $this->params['date'] : null;

                echo 'Add profit: ' . $deposit_ids . ', percent: ' . $percent . ', date: ' . $date . PHP_EOL;

                if (!$deposit_ids) {
                    die("'deposit_id' is required");
                }

                if (!$percent || $percent < 0) {
                    die("'percent' is required");
                }

                $deposit_ids = explode(',', $deposit_ids);
                $deposit_ids = array_map('trim', $deposit_ids);
                $deposit_ids = array_filter($deposit_ids);
                $deposit_ids = array_unique($deposit_ids);
                Transaction::wrap(function () use ($deposit_ids, $percent, $date) {
                    foreach ($deposit_ids as $deposit_id) {
                        $deposit_id = intval(trim($deposit_id));
                        InvestmentModule::addPoolProfit($deposit_id, $percent, $date);
                    }
                });
                break;
            case 'recreate_wallets':
                $currency = 'ltc';
                $inst = Factory::getInstance($currency);

                $last_address = 1;

                while(true) {
                    $wallets = WalletModel::queryBuilder()
                        ->columns([])
                        ->where(Where::and()
                            ->set('address', Where::OperatorEq, 'none')
                            ->set('currency', Where::OperatorEq, $currency)
                        )
                        ->limit(100)
                        ->select();

                    $wallets = WalletModel::rowsToSet($wallets);

                    if ($wallets->isEmpty()) {
                        die('End');
                        break;
                    }

                    /* @var \Models\WalletModel $wallet */
                    foreach ($wallets as $wallet) {
                        $last_address++;

                        $options = [];
                        $address = new AddressModel();
                        $address->currency = $currency;
                        $address->options = empty($options) ? '' : json_encode($options);
                        $address->user_id = $wallet->user_id;
                        $address->address = $inst->genAddress($last_address);
                        $address->created_at = date('Y-m-d H:i:s');
                        $address->save();

                        $wallet->address = $address->address;
                        $wallet->status = 'generated';
                        $wallet->save();
                    }
                }

                die('Done');
                break;
            case 'token_refer':
                $user_id = isset($this->params['user_id']) ? $this->params['user_id'] : false;
                if (!$user_id) {
                    die("'user_id' is required");
                }

                $amount = isset($this->params['amount']) ? $this->params['amount'] : false;
                if ($amount <= 0) {
                    die("'amount' is required");
                }

                $target_id = isset($this->params['target_id']) ? $this->params['target_id'] : false;
                if (!$target_id) {
                    die("'target_id' is required");
                }

                InvestmentModule::addTokenProfit($user_id, $amount, $target_id);

                echo 'oke';
                break;

            case 'add_tokens':
                $user_id = isset($this->params['user_id']) ? $this->params['user_id'] : false;
                if (!$user_id) {
                    die("'user_id' is required");
                }

                $amount = isset($this->params['amount']) ? $this->params['amount'] : false;
                if ($amount <= 0) {
                    die("'amount' is required");
                }

                $btc_price = isset($this->params['btc_price']) ? $this->params['btc_price'] : false;
                if ($btc_price <= 0) {
                    die("'btc_price' is required");
                }

                $price = isset($this->params['price']) ? $this->params['price'] : false;
                if ($price <= 0) {
                    die("'price' is required");
                }

                $refer = isset($this->params['refer']) ? (int) $this->params['refer'] : false;

                $currency = 'btc';

                $user = UserModel::get($user_id);

                $to_wallet = WalletModel::select(Where::and()
                    ->set('user_id', Where::OperatorEq, $user_id)
                    ->set('currency', Where::OperatorEq, TOKEN_ID)
                );

                if ($to_wallet->isEmpty()) {
                    $to_wallet = new WalletModel();
                    $to_wallet->user_id = $user->id;
                    $to_wallet->address = null;
                    $to_wallet->currency = TOKEN_ID;
                    $to_wallet->amount = 0;
                    $to_wallet->status = 'generated';
                    $to_wallet->save();
                } else {
                    $to_wallet = $to_wallet->first();
                }

                $rate =  $price / $btc_price;
                $sub_amount = $rate * $amount;

                Transaction::wrap(function () use ($user_id, $sub_amount, $currency, $to_wallet, $amount, $rate, $refer) {
                    if (!$to_wallet->addAmount($amount)) {
                        throw new \Exception(lang('api_error'));
                    }

                    $from_balance = BalanceModule::getFakeBalance(BalanceModel::FAKE_WALLET, $currency);
                    $to_balance = BalanceModule::getFakeBalance(BalanceModel::FAKE_WALLET, $to_wallet->currency);

                    $extra = [
                        'from_currency' => $from_balance->currency,
                        'to_currency' => $to_balance->currency,
                        'sub_amount' => $sub_amount,
                        'price' => $rate,
                    ];

                    if ($refer) {
                        InvestmentModule::addTokenProfit($refer, $amount, $user_id);
                    }

                    return BalanceModule::addHistory(
                        BalanceHistoryModel::TYPE_BUY_TOKEN,
                        $amount,
                        $user_id,
                        $from_balance,
                        $to_balance,
                        $extra
                    );
                });

                break;

            default:
                die('Unknown job: ' . $this->name);
        }
    }
}
