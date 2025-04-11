<?php

namespace com\nlf\calendar\util;

use com\nlf\calendar\Holiday;
use com\nlf\calendar\FotoFestival;
use com\nlf\calendar\TaoFestival;
use com\nlf\calendar\Solar;
use RuntimeException;

class HolidayUtil
{
    private static $SIZE = 18;
    private static $ZERO = 48;
    private static $TAG_REMOVE = '~';
    public static $NAMES = array('元旦节', '春节', '清明节', '劳动节', '端午节', '中秋节', '国庆节', '国庆中秋', '抗战胜利日');
    private static $DATA = 'Truncated';
    private static function padding($n)
    {
        return ($n < 10 ? '0' : '') . $n;
    }
    private static function buildHolidayForward($s)
    {
        $day = substr($s, 0, 8);
        $name = self::$NAMES[ord(substr($s, 8, 1)) - self::$ZERO];
        $work = ord(substr($s, 9, 1)) === self::$ZERO;
        $target = substr($s, 10, 8);
        return new Holiday($day, $name, $work, $target);
    }
    private static function buildHolidayBackward($s)
    {
        $size = strlen($s);
        $day = substr($s, $size - 18, 8);
        $name = self::$NAMES[ord(substr($s, $size - 10, 1)) - self::$ZERO];
        $work = ord(substr($s, $size - 9, 1)) === self::$ZERO;
        $target = substr($s, $size - 8);
        return new Holiday($day, $name, $work, $target);
    }
    private static function findForward($key)
    {
        $start = strpos(self::$DATA, $key);
        if (!$start) {
            return null;
        }
        $right = substr(self::$DATA, $start);
        $n = strlen($right) % self::$SIZE;
        if ($n > 0) {
            $right = substr($right, $n);
        }
        while ((strpos($right, $key) !== 0) && strlen($right) >= self::$SIZE) {
            $right = substr($right, self::$SIZE);
        }
        return $right;
    }
    private static function findBackward($key)
    {
        $start = strrpos(self::$DATA, $key);
        if (!$start) {
            return null;
        }
        $left = substr(self::$DATA, 0, $start + strlen($key));
        $size = strlen($left);
        $n = $size % self::$SIZE;
        if ($n > 0) {
            $left = substr($left, 0, $size - $n);
        }
        $size = strlen($left);
        while ((substr_compare($left, $key, -strlen($key)) !== 0) && $size >= self::$SIZE) {
            $left = substr($left, 0, $size - self::$SIZE);
            $size = strlen($left);
        }
        return $left;
    }
    private static function findHolidaysForward($key)
    {
        $l = array();
        $s = self::findForward($key);
        if (null == $s) {
            return $l;
        }
        while (strpos($s, $key) === 0) {
            $l[] = self::buildHolidayForward($s);
            $s = substr($s, self::$SIZE);
        }
        return $l;
    }
    private static function findHolidaysBackward($key)
    {
        $l = array();
        $s = self::findBackward($key);
        if (null == $s) {
            return $l;
        }
        while (substr_compare($s, $key, -strlen($key)) === 0) {
            $l[] = self::buildHolidayBackward($s);
            $s = substr($s, 0, strlen($s) - self::$SIZE);
        }
        return array_reverse($l);
    }
    public static function getHolidayByYmd($year, $month, $day)
    {
        $l = self::findHolidaysForward($year . self::padding($month) . self::padding($day));
        return empty($l) ? null : $l[0];
    }
    public static function getHoliday($ymd)
    {
        $l = self::findHolidaysForward(str_replace('-', '', $ymd));
        return empty($l) ? null : $l[0];
    }
    public static function getHolidaysByYm($year, $month)
    {
        return self::findHolidaysForward($year . self::padding($month));
    }
    public static function getHolidaysByYear($year)
    {
        return self::findHolidaysForward($year . '');
    }
    public static function getHolidays($ymd)
    {
        return self::findHolidaysForward(str_replace('-', '', $ymd));
    }
    public static function getHolidaysByTargetYmd($year, $month, $day)
    {
        return self::findHolidaysBackward($year . self::padding($month) . self::padding($day));
    }
    public static function getHolidaysByTarget($ymd)
    {
        return self::findHolidaysBackward(str_replace('-', '', $ymd));
    }
    public static function fix($names, $data)
    {
        if (null != $names) {
            self::$NAMES = $names;
        }
        if (null == $data) {
            return;
        }
        $append = '';
        while (strlen($data) >= self::$SIZE) {
            $segment = substr($data, 0, self::$SIZE);
            $day = substr($segment, 0, 8);
            $remove = strcmp(self::$TAG_REMOVE, substr($segment, 8, 1)) == 0;
            $holiday = self::getHoliday($day);
            if (null == $holiday) {
                if (!$remove) {
                    $append .= $segment;
                }
            } else {
                $nameIndex = -1;
                for ($i = 0, $j = count(self::$NAMES); $i < $j; $i++) {
                    if (strcmp(self::$NAMES[$i], $holiday->getName()) == 0) {
                        $nameIndex = $i;
                        break;
                    }
                }
                if ($nameIndex > -1) {
                    $old = $day . chr($nameIndex + self::$ZERO) . ($holiday->isWork() ? '0' : '1') . str_replace('-', '', $holiday->getTarget());
                    self::$DATA = str_replace($old, $remove ? '' : $segment, self::$DATA);
                }
            }
            $data = substr($data, self::$SIZE);
        }
        if (strlen($append) > 0) {
            self::$DATA .= $append;
        }
    }
}
class FotoUtil
{
    public static $DAY_ZHAI_GUAN_YIN = array('1-8', '2-7', '2-9', '2-19', '3-3', '3-6', '3-13', '4-22', '5-3', '5-17', '6-16', '6-18', '6-19', '6-23', '7-13', '8-16', '9-19', '9-23', '10-2', '11-19', '11-24', '12-25');
    public static $XIU_27 = array('角', '亢', '氐', '房', '心', '尾', '箕', '斗', '女', '虚', '危', '室', '壁', '奎', '娄', '胃', '昴', '毕', '觜', '参', '井', '鬼', '柳', '星', '张', '翼', '轸');
    private static $XIU_OFFSET = array(11, 13, 15, 17, 19, 21, 24, 0, 2, 4, 7, 9);
    private static $FESTIVAL;
    public static $OTHER_FESTIVAL = array('1-1' => array('弥勒菩萨圣诞'), '1-6' => array('定光佛圣诞'), '2-8' => array('释迦牟尼佛出家'), '2-15' => array('释迦牟尼佛涅槃'), '2-19' => array('观世音菩萨圣诞'), '2-21' => array('普贤菩萨圣诞'), '3-16' => array('准提菩萨圣诞'), '4-4' => array('文殊菩萨圣诞'), '4-8' => array('释迦牟尼佛圣诞'), '4-15' => array('佛吉祥日'), '4-28' => array('药王菩萨圣诞'), '5-13' => array('伽蓝菩萨圣诞'), '6-3' => array('韦驮菩萨圣诞'), '6-19' => array('观音菩萨成道'), '7-13' => array('大势至菩萨圣诞'), '7-15' => array('佛欢喜日'), '7-24' => array('龙树菩萨圣诞'), '7-30' => array('地藏菩萨圣诞'), '8-15' => array('月光菩萨圣诞'), '8-22' => array('燃灯佛圣诞'), '9-9' => array('摩利支天菩萨圣诞'), '9-19' => array('观世音菩萨出家'), '9-30' => array('药师琉璃光佛圣诞'), '10-5' => array('达摩祖师圣诞'), '10-20' => array('文殊菩萨出家'), '11-17' => array('阿弥陀佛圣诞'), '11-19' => array('日光菩萨圣诞'), '12-8' => array('释迦牟尼佛成道'), '12-23' => array('监斋菩萨圣诞'), '12-29' => array('华严菩萨圣诞'));
    public static function getFestivals($md)
    {
        if (null == self::$FESTIVAL) {
            self::init();
        }
        $l = array();
        if (!empty(self::$FESTIVAL[$md])) {
            $l = self::$FESTIVAL[$md];
        }
        return $l;
    }
    public static function getXiu($month, $day)
    {
        return self::$XIU_27[(self::$XIU_OFFSET[abs($month) - 1] + $day - 1) % count(self::$XIU_27)];
    }
    private static function init()
    {
        $DJ = '犯者夺纪';
        $JS = '犯者减寿';
        $SS = '犯者损寿';
        $XL = '犯者削禄夺纪';
        $JW = '犯者三年内夫妇俱亡';
        $Y = new FotoFestival('杨公忌');
        $T = new FotoFestival('四天王巡行', '', true);
        $D = new FotoFestival('斗降', $DJ, true);
        $S = new FotoFestival('月朔', $DJ, true);
        $W = new FotoFestival('月望', $DJ, true);
        $H = new FotoFestival('月晦', $JS, true);
        $L = new FotoFestival('雷斋日', $JS, true);
        $J = new FotoFestival('九毒日', '犯者夭亡，奇祸不测');
        $R = new FotoFestival('人神在阴', '犯者得病', true, '宜先一日即戒');
        $M = new FotoFestival('司命奏事', $JS, true, '如月小，即戒廿九');
        $HH = new FotoFestival('月晦', $JS, true, '如月小，即戒廿九');
        self::$FESTIVAL = array('1-1' => array(new FotoFestival('天腊，玉帝校世人神气禄命', $XL), $S), '1-3' => array(new FotoFestival('万神都会', $DJ), $D), '1-5' => array(new FotoFestival('五虚忌')), '1-6' => array(new FotoFestival('六耗忌'), $L), '1-7' => array(new FotoFestival('上会日', $SS)), '1-8' => array(new FotoFestival('五殿阎罗天子诞', $DJ), $T), '1-9' => array(new FotoFestival('玉皇上帝诞', $DJ)), '1-13' => array($Y), '1-14' => array(new FotoFestival('三元降', $JS), $T), '1-15' => array(new FotoFestival('三元降', $JS), new FotoFestival('上元神会', $DJ), $W, $T), '1-16' => array(new FotoFestival('三元降', $JS)), '1-19' => array(new FotoFestival('长春真人诞')), '1-23' => array(new FotoFestival('三尸神奏事'), $T), '1-25' => array($H, new FotoFestival('天地仓开日', '犯者损寿，子带疾')), '1-27' => array($D), '1-28' => array($R), '1-29' => array($T), '1-30' => array($HH, $M, $T), '2-1' => array(new FotoFestival('一殿秦广王诞', $DJ), $S), '2-2' => array(new FotoFestival('万神都会', $DJ), new FotoFestival('福德土地正神诞', '犯者得祸')), '2-3' => array(new FotoFestival('文昌帝君诞', $XL), $D), '2-6' => array(new FotoFestival('东华帝君诞'), $L), '2-8' => array(new FotoFestival('释迦牟尼佛出家', $DJ), new FotoFestival('三殿宋帝王诞', $DJ), new FotoFestival('张大帝诞', $DJ), $T), '2-11' => array($Y), '2-14' => array($T), '2-15' => array(new FotoFestival('释迦牟尼佛涅槃', $XL), new FotoFestival('太上老君诞', $XL), new FotoFestival('月望', $XL, true), $T), '2-17' => array(new FotoFestival('东方杜将军诞')), '2-18' => array(new FotoFestival('四殿五官王诞', $XL), new FotoFestival('至圣先师孔子讳辰', $XL)), '2-19' => array(new FotoFestival('观音大士诞', $DJ)), '2-21' => array(new FotoFestival('普贤菩萨诞')), '2-23' => array($T), '2-25' => array($H), '2-27' => array($D), '2-28' => array($R), '2-29' => array($T), '2-30' => array($HH, $M, $T), '3-1' => array(new FotoFestival('二殿楚江王诞', $DJ), $S), '3-3' => array(new FotoFestival('玄天上帝诞', $DJ), $D), '3-6' => array($L), '3-8' => array(new FotoFestival('六殿卞城王诞', $DJ), $T), '3-9' => array(new FotoFestival('牛鬼神出', '犯者产恶胎'), $Y), '3-12' => array(new FotoFestival('中央五道诞')), '3-14' => array($T), '3-15' => array(new FotoFestival('昊天上帝诞', $DJ), new FotoFestival('玄坛诞', $DJ), $W, $T), '3-16' => array(new FotoFestival('准提菩萨诞', $DJ)), '3-19' => array(new FotoFestival('中岳大帝诞'), new FotoFestival('后土娘娘诞'), new FotoFestival('三茅降')), '3-20' => array(new FotoFestival('天地仓开日', $SS), new FotoFestival('子孙娘娘诞')), '3-23' => array($T), '3-25' => array($H), '3-27' => array(new FotoFestival('七殿泰山王诞'), $D), '3-28' => array($R, new FotoFestival('苍颉至圣先师诞', $XL), new FotoFestival('东岳大帝诞')), '3-29' => array($T), '3-30' => array($HH, $M, $T), '4-1' => array(new FotoFestival('八殿都市王诞', $DJ), $S), '4-3' => array($D), '4-4' => array(new FotoFestival('万神善会', '犯者失瘼夭胎'), new FotoFestival('文殊菩萨诞')), '4-6' => array($L), '4-7' => array(new FotoFestival('南斗、北斗、西斗同降', $JS), $Y), '4-8' => array(new FotoFestival('释迦牟尼佛诞', $DJ), new FotoFestival('万神善会', '犯者失瘼夭胎'), new FotoFestival('善恶童子降', '犯者血死'), new FotoFestival('九殿平等王诞'), $T), '4-14' => array(new FotoFestival('纯阳祖师诞', $JS), $T), '4-15' => array($W, new FotoFestival('钟离祖师诞'), $T), '4-16' => array(new FotoFestival('天地仓开日', $SS)), '4-17' => array(new FotoFestival('十殿转轮王诞', $DJ)), '4-18' => array(new FotoFestival('天地仓开日', $SS), new FotoFestival('紫徽大帝诞', $SS)), '4-20' => array(new FotoFestival('眼光圣母诞')), '4-23' => array($T), '4-25' => array($H), '4-27' => array($D), '4-28' => array($R), '4-29' => array($T), '4-30' => array($HH, $M, $T), '5-1' => array(new FotoFestival('南极长生大帝诞', $DJ), $S), '5-3' => array($D), '5-5' => array(new FotoFestival('地腊', $XL), new FotoFestival('五帝校定生人官爵', $XL), $J, $Y), '5-6' => array($J, $L), '5-7' => array($J), '5-8' => array(new FotoFestival('南方五道诞'), $T), '5-11' => array(new FotoFestival('天地仓开日', $SS), new FotoFestival('天下都城隍诞')), '5-12' => array(new FotoFestival('炳灵公诞')), '5-13' => array(new FotoFestival('关圣降', $XL)), '5-14' => array(new FotoFestival('夜子时为天地交泰', $JW), $T), '5-15' => array($W, $J, $T), '5-16' => array(new FotoFestival('九毒日', $JW), new FotoFestival('天地元气造化万物之辰', $JW)), '5-17' => array($J), '5-18' => array(new FotoFestival('张天师诞')), '5-22' => array(new FotoFestival('孝娥神诞', $DJ)), '5-23' => array($T), '5-25' => array($J, $H), '5-26' => array($J), '5-27' => array($J, $D), '5-28' => array($R), '5-29' => array($T), '5-30' => array($HH, $M, $T), '6-1' => array($S), '6-3' => array(new FotoFestival('韦驮菩萨圣诞'), $D, $Y), '6-5' => array(new FotoFestival('南赡部洲转大轮', $SS)), '6-6' => array(new FotoFestival('天地仓开日', $SS), $L), '6-8' => array($T), '6-10' => array(new FotoFestival('金粟如来诞')), '6-14' => array($T), '6-15' => array($W, $T), '6-19' => array(new FotoFestival('观世音菩萨成道', $DJ)), '6-23' => array(new FotoFestival('南方火神诞', '犯者遭回禄'), $T), '6-24' => array(new FotoFestival('雷祖诞', $XL), new FotoFestival('关帝诞', $XL)), '6-25' => array($H), '6-27' => array($D), '6-28' => array($R), '6-29' => array($T), '6-30' => array($HH, $M, $T), '7-1' => array($S, $Y), '7-3' => array($D), '7-5' => array(new FotoFestival('中会日', $SS, false, '一作初七')), '7-6' => array($L), '7-7' => array(new FotoFestival('道德腊', $XL), new FotoFestival('五帝校生人善恶', $XL), new FotoFestival('魁星诞', $XL)), '7-8' => array($T), '7-10' => array(new FotoFestival('阴毒日', '', false, '大忌')), '7-12' => array(new FotoFestival('长真谭真人诞')), '7-13' => array(new FotoFestival('大势至菩萨诞', $JS)), '7-14' => array(new FotoFestival('三元降', $JS), $T), '7-15' => array($W, new FotoFestival('三元降', $DJ), new FotoFestival('地官校籍', $DJ), $T), '7-16' => array(new FotoFestival('三元降', $JS)), '7-18' => array(new FotoFestival('西王母诞', $DJ)), '7-19' => array(new FotoFestival('太岁诞', $DJ)), '7-22' => array(new FotoFestival('增福财神诞', $XL)), '7-23' => array($T), '7-25' => array($H), '7-27' => array($D), '7-28' => array($R), '7-29' => array($Y, $T), '7-30' => array(new FotoFestival('地藏菩萨诞', $DJ), $HH, $M, $T), '8-1' => array($S, new FotoFestival('许真君诞')), '8-3' => array($D, new FotoFestival('北斗诞', $XL), new FotoFestival('司命灶君诞', '犯者遭回禄')), '8-5' => array(new FotoFestival('雷声大帝诞', $DJ)), '8-6' => array($L), '8-8' => array($T), '8-10' => array(new FotoFestival('北斗大帝诞')), '8-12' => array(new FotoFestival('西方五道诞')), '8-14' => array($T), '8-15' => array($W, new FotoFestival('太明朝元', '犯者暴亡', false, '宜焚香守夜'), $T), '8-16' => array(new FotoFestival('天曹掠刷真君降', '犯者贫夭')), '8-18' => array(new FotoFestival('天人兴福之辰', '', false, '宜斋戒，存想吉事')), '8-23' => array(new FotoFestival('汉恒候张显王诞'), $T), '8-24' => array(new FotoFestival('灶君夫人诞')), '8-25' => array($H), '8-27' => array($D, new FotoFestival('至圣先师孔子诞', $XL), $Y), '8-28' => array($R, new FotoFestival('四天会事')), '8-29' => array($T), '8-30' => array(new FotoFestival('诸神考校', '犯者夺算'), $HH, $M, $T), '9-1' => array($S, new FotoFestival('南斗诞', $XL), new FotoFestival('北斗九星降世', $DJ, false, '此九日俱宜斋戒')), '9-3' => array($D, new FotoFestival('五瘟神诞')), '9-6' => array($L), '9-8' => array($T), '9-9' => array(new FotoFestival('斗母诞', $XL), new FotoFestival('酆都大帝诞'), new FotoFestival('玄天上帝飞升')), '9-10' => array(new FotoFestival('斗母降', $DJ)), '9-11' => array(new FotoFestival('宜戒')), '9-13' => array(new FotoFestival('孟婆尊神诞')), '9-14' => array($T), '9-15' => array($W, $T), '9-17' => array(new FotoFestival('金龙四大王诞', '犯者遭水厄')), '9-19' => array(new FotoFestival('日宫月宫会合', $JS), new FotoFestival('观世音菩萨诞', $JS)), '9-23' => array($T), '9-25' => array($H, $Y), '9-27' => array($D), '9-28' => array($R), '9-29' => array($T), '9-30' => array(new FotoFestival('药师琉璃光佛诞', '犯者危疾'), $HH, $M, $T), '10-1' => array($S, new FotoFestival('民岁腊', $DJ), new FotoFestival('四天王降', '犯者一年内死')), '10-3' => array($D, new FotoFestival('三茅诞')), '10-5' => array(new FotoFestival('下会日', $JS), new FotoFestival('达摩祖师诞', $JS)), '10-6' => array($L, new FotoFestival('天曹考察', $DJ)), '10-8' => array(new FotoFestival('佛涅槃日', '', false, '大忌色欲'), $T), '10-10' => array(new FotoFestival('四天王降', '犯者一年内死')), '10-11' => array(new FotoFestival('宜戒')), '10-14' => array(new FotoFestival('三元降', $JS), $T), '10-15' => array($W, new FotoFestival('三元降', $DJ), new FotoFestival('下元水府校籍', $DJ), $T), '10-16' => array(new FotoFestival('三元降', $JS), $T), '10-23' => array($Y, $T), '10-25' => array($H), '10-27' => array($D, new FotoFestival('北极紫徽大帝降')), '10-28' => array($R), '10-29' => array($T), '10-30' => array($HH, $M, $T), '11-1' => array($S), '11-3' => array($D), '11-4' => array(new FotoFestival('至圣先师孔子诞', $XL)), '11-6' => array(new FotoFestival('西岳大帝诞')), '11-8' => array($T), '11-11' => array(new FotoFestival('天地仓开日', $DJ), new FotoFestival('太乙救苦天尊诞', $DJ)), '11-14' => array($T), '11-15' => array(new FotoFestival('月望', '上半夜犯男死 下半夜犯女死'), new FotoFestival('四天王巡行', '上半夜犯男死 下半夜犯女死')), '11-17' => array(new FotoFestival('阿弥陀佛诞')), '11-19' => array(new FotoFestival('太阳日宫诞', '犯者得奇祸')), '11-21' => array($Y), '11-23' => array(new FotoFestival('张仙诞', '犯者绝嗣'), $T), '11-25' => array(new FotoFestival('掠刷大夫降', '犯者遭大凶'), $H), '11-26' => array(new FotoFestival('北方五道诞')), '11-27' => array($D), '11-28' => array($R), '11-29' => array($T), '11-30' => array($HH, $M, $T), '12-1' => array($S), '12-3' => array($D), '12-6' => array(new FotoFestival('天地仓开日', $JS), $L), '12-7' => array(new FotoFestival('掠刷大夫降', '犯者得恶疾')), '12-8' => array(new FotoFestival('王侯腊', $DJ), new FotoFestival('释迦如来成佛之辰'), $T, new FotoFestival('初旬内戊日，亦名王侯腊', $DJ)), '12-12' => array(new FotoFestival('太素三元君朝真')), '12-14' => array($T), '12-15' => array($W, $T), '12-16' => array(new FotoFestival('南岳大帝诞')), '12-19' => array($Y), '12-20' => array(new FotoFestival('天地交道', '犯者促寿')), '12-21' => array(new FotoFestival('天猷上帝诞')), '12-23' => array(new FotoFestival('五岳诞降'), $T), '12-24' => array(new FotoFestival('司今朝天奏人善恶', '犯者得大祸')), '12-25' => array(new FotoFestival('三清玉帝同降，考察善恶', '犯者得奇祸'), $H), '12-27' => array($D), '12-28' => array($R), '12-29' => array(new FotoFestival('华严菩萨诞'), $T), '12-30' => array(new FotoFestival('诸神下降，察访善恶', '犯者男女俱亡')));
    }
}
class TaoUtil
{
    public static $SAN_HUI = array('1-7', '7-7', '10-15');
    public static $SAN_YUAN = array('1-15', '7-15', '10-15');
    public static $WU_LA = array('1-1', '5-5', '7-7', '10-1', '12-8');
    public static $AN_WU = array('未', '戌', '辰', '寅', '午', '子', '酉', '申', '巳', '亥', '卯', '丑');
    public static $BA_HUI = array('丙午' => '天会', '壬午' => '地会', '壬子' => '人会', '庚午' => '日会', '庚申' => '月会', '辛酉' => '星辰会', '甲辰' => '五行会', '甲戌' => '四时会');
    public static $BA_JIE = array('立春' => '东北方度仙上圣天尊同梵炁始青天君下降', '春分' => '东方玉宝星上天尊同青帝九炁天君下降', '立夏' => '东南方好生度命天尊同梵炁始丹天君下降', '夏至' => '南方玄真万福天尊同赤帝三炁天君下降', '立秋' => '西南方太灵虚皇天尊同梵炁始素天君下降', '秋分' => '西方太妙至极天尊同白帝七炁天君下降', '立冬' => '西北方无量太华天尊同梵炁始玄天君下降', '冬至' => '北方玄上玉宸天尊同黑帝五炁天君下降');
    private static $FESTIVAL;
    public static function getFestivals($md)
    {
        if (null == self::$FESTIVAL) {
            self::init();
        }
        $l = array();
        if (!empty(self::$FESTIVAL[$md])) {
            $l = self::$FESTIVAL[$md];
        }
        return $l;
    }
    private static function init()
    {
        self::$FESTIVAL = array('1-1' => array(new TaoFestival('天腊之辰', '天腊，此日五帝会于东方九炁青天')), '1-3' => array(new TaoFestival('郝真人圣诞'), new TaoFestival('孙真人圣诞')), '1-5' => array(new TaoFestival('孙祖清静元君诞')), '1-7' => array(new TaoFestival('举迁赏会', '此日上元赐福，天官同地水二官考校罪福')), '1-9' => array(new TaoFestival('玉皇上帝圣诞')), '1-13' => array(new TaoFestival('关圣帝君飞升')), '1-15' => array(new TaoFestival('上元天官圣诞'), new TaoFestival('老祖天师圣诞')), '1-19' => array(new TaoFestival('长春邱真人(邱处机)圣诞')), '1-28' => array(new TaoFestival('许真君(许逊天师)圣诞')), '2-1' => array(new TaoFestival('勾陈天皇大帝圣诞'), new TaoFestival('长春刘真人(刘渊然)圣诞')), '2-2' => array(new TaoFestival('土地正神诞'), new TaoFestival('姜太公圣诞')), '2-3' => array(new TaoFestival('文昌梓潼帝君圣诞')), '2-6' => array(new TaoFestival('东华帝君圣诞')), '2-13' => array(new TaoFestival('度人无量葛真君圣诞')), '2-15' => array(new TaoFestival('太清道德天尊(太上老君)圣诞')), '2-19' => array(new TaoFestival('慈航真人圣诞')), '3-1' => array(new TaoFestival('谭祖(谭处端)长真真人圣诞')), '3-3' => array(new TaoFestival('玄天上帝圣诞')), '3-6' => array(new TaoFestival('眼光娘娘圣诞')), '3-15' => array(new TaoFestival('天师张大真人圣诞'), new TaoFestival('财神赵公元帅圣诞')), '3-16' => array(new TaoFestival('三茅真君得道之辰'), new TaoFestival('中岳大帝圣诞')), '3-18' => array(new TaoFestival('王祖(王处一)玉阳真人圣诞'), new TaoFestival('后土娘娘圣诞')), '3-19' => array(new TaoFestival('太阳星君圣诞')), '3-20' => array(new TaoFestival('子孙娘娘圣诞')), '3-23' => array(new TaoFestival('天后妈祖圣诞')), '3-26' => array(new TaoFestival('鬼谷先师诞')), '3-28' => array(new TaoFestival('东岳大帝圣诞')), '4-1' => array(new TaoFestival('长生谭真君成道之辰')), '4-10' => array(new TaoFestival('何仙姑圣诞')), '4-14' => array(new TaoFestival('吕祖纯阳祖师圣诞')), '4-15' => array(new TaoFestival('钟离祖师圣诞')), '4-18' => array(new TaoFestival('北极紫微大帝圣诞'), new TaoFestival('泰山圣母碧霞元君诞'), new TaoFestival('华佗神医先师诞')), '4-20' => array(new TaoFestival('眼光圣母娘娘诞')), '4-28' => array(new TaoFestival('神农先帝诞')), '5-1' => array(new TaoFestival('南极长生大帝圣诞')), '5-5' => array(new TaoFestival('地腊之辰', '地腊，此日五帝会于南方三炁丹天'), new TaoFestival('南方雷祖圣诞'), new TaoFestival('地祗温元帅圣诞'), new TaoFestival('雷霆邓天君圣诞')), '5-11' => array(new TaoFestival('城隍爷圣诞')), '5-13' => array(new TaoFestival('关圣帝君降神'), new TaoFestival('关平太子圣诞')), '5-18' => array(new TaoFestival('张天师圣诞')), '5-20' => array(new TaoFestival('马祖丹阳真人圣诞')), '5-29' => array(new TaoFestival('紫青白祖师圣诞')), '6-1' => array(new TaoFestival('南斗星君下降')), '6-2' => array(new TaoFestival('南斗星君下降')), '6-3' => array(new TaoFestival('南斗星君下降')), '6-4' => array(new TaoFestival('南斗星君下降')), '6-5' => array(new TaoFestival('南斗星君下降')), '6-6' => array(new TaoFestival('南斗星君下降')), '6-10' => array(new TaoFestival('刘海蟾祖师圣诞')), '6-15' => array(new TaoFestival('灵官王天君圣诞')), '6-19' => array(new TaoFestival('慈航(观音)成道日')), '6-23' => array(new TaoFestival('火神圣诞')), '6-24' => array(new TaoFestival('南极大帝中方雷祖圣诞'), new TaoFestival('关圣帝君圣诞')), '6-26' => array(new TaoFestival('二郎真君圣诞')), '7-7' => array(new TaoFestival('道德腊之辰', '道德腊，此日五帝会于西方七炁素天'), new TaoFestival('庆生中会', '此日中元赦罪，地官同天水二官考校罪福')), '7-12' => array(new TaoFestival('西方雷祖圣诞')), '7-15' => array(new TaoFestival('中元地官大帝圣诞')), '7-18' => array(new TaoFestival('王母娘娘圣诞')), '7-20' => array(new TaoFestival('刘祖(刘处玄)长生真人圣诞')), '7-22' => array(new TaoFestival('财帛星君文财神增福相公李诡祖圣诞')), '7-26' => array(new TaoFestival('张三丰祖师圣诞')), '8-1' => array(new TaoFestival('许真君飞升日')), '8-3' => array(new TaoFestival('九天司命灶君诞')), '8-5' => array(new TaoFestival('北方雷祖圣诞')), '8-10' => array(new TaoFestival('北岳大帝诞辰')), '8-15' => array(new TaoFestival('太阴星君诞')), '9-1' => array(new TaoFestival('北斗九皇降世之辰')), '9-2' => array(new TaoFestival('北斗九皇降世之辰')), '9-3' => array(new TaoFestival('北斗九皇降世之辰')), '9-4' => array(new TaoFestival('北斗九皇降世之辰')), '9-5' => array(new TaoFestival('北斗九皇降世之辰')), '9-6' => array(new TaoFestival('北斗九皇降世之辰')), '9-7' => array(new TaoFestival('北斗九皇降世之辰')), '9-8' => array(new TaoFestival('北斗九皇降世之辰')), '9-9' => array(new TaoFestival('北斗九皇降世之辰'), new TaoFestival('斗姥元君圣诞'), new TaoFestival('重阳帝君圣诞'), new TaoFestival('玄天上帝飞升'), new TaoFestival('酆都大帝圣诞')), '9-22' => array(new TaoFestival('增福财神诞')), '9-23' => array(new TaoFestival('萨翁真君圣诞')), '9-28' => array(new TaoFestival('五显灵官马元帅圣诞')), '10-1' => array(new TaoFestival('民岁腊之辰', '民岁腊，此日五帝会于北方五炁黑天'), new TaoFestival('东皇大帝圣诞')), '10-3' => array(new TaoFestival('三茅应化真君圣诞')), '10-6' => array(new TaoFestival('天曹诸司五岳五帝圣诞')), '10-15' => array(new TaoFestival('下元水官大帝圣诞'), new TaoFestival('建生大会', '此日下元解厄，水官同天地二官考校罪福')), '10-18' => array(new TaoFestival('地母娘娘圣诞')), '10-19' => array(new TaoFestival('长春邱真君飞升')), '10-20' => array(new TaoFestival('虚靖天师(即三十代天师弘悟张真人)诞')), '11-6' => array(new TaoFestival('西岳大帝圣诞')), '11-9' => array(new TaoFestival('湘子韩祖圣诞')), '11-11' => array(new TaoFestival('太乙救苦天尊圣诞')), '11-26' => array(new TaoFestival('北方五道圣诞')), '12-8' => array(new TaoFestival('王侯腊之辰', '王侯腊，此日五帝会于上方玄都玉京')), '12-16' => array(new TaoFestival('南岳大帝圣诞'), new TaoFestival('福德正神诞')), '12-20' => array(new TaoFestival('鲁班先师圣诞')), '12-21' => array(new TaoFestival('天猷上帝圣诞')), '12-22' => array(new TaoFestival('重阳祖师圣诞')), '12-23' => array(new TaoFestival('祭灶王', '最适宜谢旧年太岁，开启拜新年太岁')), '12-25' => array(new TaoFestival('玉帝巡天'), new TaoFestival('天神下降')), '12-29' => array(new TaoFestival('清静孙真君(孙不二)成道')));
    }
}
class LunarUtil
{
    public static $BASE_MONTH_ZHI_INDEX = 2;
    public static $XUN = array('甲子', '甲戌', '甲申', '甲午', '甲辰', '甲寅');
    public static $XUN_KONG = array('戌亥', '申酉', '午未', '辰巳', '寅卯', '子丑');
    public static $LIU_YAO = array('先胜', '友引', '先负', '佛灭', '大安', '赤口');
    public static $HOU = array('初候', '二候', '三候');
    public static $WU_HOU = array('蚯蚓结', '麋角解', '水泉动', '雁北乡', '鹊始巢', '雉始雊', '鸡始乳', '征鸟厉疾', '水泽腹坚', '东风解冻', '蛰虫始振', '鱼陟负冰', '獭祭鱼', '候雁北', '草木萌动', '桃始华', '仓庚鸣', '鹰化为鸠', '玄鸟至', '雷乃发声', '始电', '桐始华', '田鼠化为鴽', '虹始见', '萍始生', '鸣鸠拂奇羽', '戴胜降于桑', '蝼蝈鸣', '蚯蚓出', '王瓜生', '苦菜秀', '靡草死', '麦秋至', '螳螂生', '鵙始鸣', '反舌无声', '鹿角解', '蜩始鸣', '半夏生', '温风至', '蟋蟀居壁', '鹰始挚', '腐草为萤', '土润溽暑', '大雨行时', '凉风至', '白露降', '寒蝉鸣', '鹰乃祭鸟', '天地始肃', '禾乃登', '鸿雁来', '玄鸟归', '群鸟养羞', '雷始收声', '蛰虫坯户', '水始涸', '鸿雁来宾', '雀入大水为蛤', '菊有黄花', '豺乃祭兽', '草木黄落', '蛰虫咸俯', '水始冰', '地始冻', '雉入大水为蜃', '虹藏不见', '天气上升地气下降', '闭塞而成冬', '鹖鴠不鸣', '虎始交', '荔挺出');
    public static $GAN = array('', '甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸');
    public static $POSITION_XI = array('', '艮', '乾', '坤', '离', '巽', '艮', '乾', '坤', '离', '巽');
    public static $POSITION_YANG_GUI = array('', '坤', '坤', '兑', '乾', '艮', '坎', '离', '艮', '震', '巽');
    public static $POSITION_YIN_GUI = array('', '艮', '坎', '乾', '兑', '坤', '坤', '艮', '离', '巽', '震');
    public static $POSITION_FU = array('', '巽', '巽', '震', '震', '坎', '离', '坤', '坤', '乾', '兑');
    public static $POSITION_FU_2 = array('', '坎', '坤', '乾', '巽', '艮', '坎', '坤', '乾', '巽', '艮');
    public static $POSITION_CAI = array('', '艮', '艮', '坤', '坤', '坎', '坎', '震', '震', '离', '离');
    public static $POSITION_TAI_SUI_YEAR = array('坎', '艮', '艮', '震', '巽', '巽', '离', '坤', '坤', '兑', '坎', '坎');
    public static $POSITION_GAN = array('震', '震', '离', '离', '中', '中', '兑', '兑', '坎', '坎');
    public static $POSITION_ZHI = array('坎', '中', '震', '震', '中', '离', '离', '中', '兑', '兑', '中', '坎');
    public static $POSITION_TAI_DAY = array('占门碓 外东南', '碓磨厕 外东南', '厨灶炉 外正南', '仓库门 外正南', '房床栖 外正南', '占门床 外正南', '占碓磨 外正南', '厨灶厕 外西南', '仓库炉 外西南', '房床门 外西南', '占门栖 外西南', '碓磨床 外西南', '厨灶碓 外西南', '仓库厕 外正西', '房床炉 外正西', '占大门 外正西', '碓磨栖 外正西', '厨灶床 外正西', '仓库碓 外西北', '房床厕 外西北', '占门炉 外西北', '碓磨门 外西北', '厨灶栖 外西北', '仓库床 外西北', '房床碓 外正北', '占门厕 外正北', '碓磨炉 外正北', '厨灶门 外正北', '仓库栖 外正北', '占房床 房内北', '占门碓 房内北', '碓磨厕 房内北', '厨灶炉 房内北', '仓库门 房内北', '床房栖 房内中', '占门床 房内中', '占碓磨 房内南', '厨灶厕 房内南', '仓库炉 房内南', '房床门 房内西', '占门栖 房内东', '碓磨床 房内东', '厨灶碓 房内东', '仓库厕 房内东', '房床炉 房内中', '占大门 外东北', '碓磨栖 外东北', '厨灶床 外东北', '仓库碓 外东北', '房床厕 外东北', '占门炉 外东北', '碓磨门 外正东', '厨灶栖 外正东', '仓库床 外正东', '房床碓 外正东', '占门厕 外正东', '碓磨炉 外东南', '厨灶门 外东南', '仓库栖 外东南', '占房床 外东南');
    public static $POSITION_TAI_MONTH = array('占房床', '占户窗', '占门堂', '占厨灶', '占房床', '占床仓', '占碓磨', '占厕户', '占门房', '占房床', '占灶炉', '占房床');
    public static $ZHI = array('', '子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥');
    public static $JIA_ZI = array('甲子', '乙丑', '丙寅', '丁卯', '戊辰', '己巳', '庚午', '辛未', '壬申', '癸酉', '甲戌', '乙亥', '丙子', '丁丑', '戊寅', '己卯', '庚辰', '辛巳', '壬午', '癸未', '甲申', '乙酉', '丙戌', '丁亥', '戊子', '己丑', '庚寅', '辛卯', '壬辰', '癸巳', '甲午', '乙未', '丙申', '丁酉', '戊戌', '己亥', '庚子', '辛丑', '壬寅', '癸卯', '甲辰', '乙巳', '丙午', '丁未', '戊申', '己酉', '庚戌', '辛亥', '壬子', '癸丑', '甲寅', '乙卯', '丙辰', '丁巳', '戊午', '己未', '庚申', '辛酉', '壬戌', '癸亥');
    public static $ZHI_XING = array('', '建', '除', '满', '平', '定', '执', '破', '危', '成', '收', '开', '闭');
    public static $TIAN_SHEN = array('', '青龙', '明堂', '天刑', '朱雀', '金匮', '天德', '白虎', '玉堂', '天牢', '玄武', '司命', '勾陈');
    private static $YI_JI = array('祭祀', '祈福', '求嗣', '开光', '塑绘', '齐醮', '斋醮', '沐浴', '酬神', '造庙', '祀灶', '焚香', '谢土', '出火', '雕刻', '嫁娶', '订婚', '纳采', '问名', '纳婿', '归宁', '安床', '合帐', '冠笄', '订盟', '进人口', '裁衣', '挽面', '开容', '修坟', '启钻', '破土', '安葬', '立碑', '成服', '除服', '开生坟', '合寿木', '入殓', '移柩', '普渡', '入宅', '安香', '安门', '修造', '起基', '动土', '上梁', '竖柱', '开井开池', '作陂放水', '拆卸', '破屋', '坏垣', '补垣', '伐木做梁', '作灶', '解除', '开柱眼', '穿屏扇架', '盖屋合脊', '开厕', '造仓', '塞穴', '平治道涂', '造桥', '作厕', '筑堤', '开池', '伐木', '开渠', '掘井', '扫舍', '放水', '造屋', '合脊', '造畜稠', '修门', '定磉', '作梁', '修饰垣墙', '架马', '开市', '挂匾', '纳财', '求财', '开仓', '买车', '置产', '雇佣', '出货财', '安机械', '造车器', '经络', '酝酿', '作染', '鼓铸', '造船', '割蜜', '栽种', '取渔', '结网', '牧养', '安碓磑', '习艺', '入学', '理发', '探病', '见贵', '乘船', '渡水', '针灸', '出行', '移徙', '分居', '剃头', '整手足甲', '纳畜', '捕捉', '畋猎', '教牛马', '会亲友', '赴任', '求医', '治病', '词讼', '起基动土', '破屋坏垣', '盖屋', '造仓库', '立券交易', '交易', '立券', '安机', '会友', '求医疗病', '诸事不宜', '馀事勿取', '行丧', '断蚁', '归岫', '无');
    private static $DAY_YI_JI = 'Truncated';
    private static $TIME_YI_JI = 'Truncated';
    private static $SHEN_SHA = array('无', '天恩', '母仓', '时阳', '生气', '益后', '青龙', '灾煞', '天火', '四忌', '八龙', '复日', '续世', '明堂', '月煞', '月虚', '血支', '天贼', '五虚', '土符', '归忌', '血忌', '月德', '月恩', '四相', '王日', '天仓', '不将', '要安', '五合', '鸣吠对', '月建', '小时', '土府', '往亡', '天刑', '天德', '官日', '吉期', '玉宇', '大时', '大败', '咸池', '朱雀', '守日', '天巫', '福德', '六仪', '金堂', '金匮', '厌对', '招摇', '九空', '九坎', '九焦', '相日', '宝光', '天罡', '死神', '月刑', '月害', '游祸', '重日', '时德', '民日', '三合', '临日', '天马', '时阴', '鸣吠', '死气', '地囊', '白虎', '月德合', '敬安', '玉堂', '普护', '解神', '小耗', '天德合', '月空', '驿马', '天后', '除神', '月破', '大耗', '五离', '天牢', '阴德', '福生', '天吏', '致死', '元武', '阳德', '天喜', '天医', '司命', '月厌', '地火', '四击', '大煞', '大会', '天愿', '六合', '五富', '圣心', '河魁', '劫煞', '四穷', '勾陈', '触水龙', '八风', '天赦', '五墓', '八专', '阴错', '四耗', '阳错', '四废', '三阴', '小会', '阴道冲阳', '单阴', '孤辰', '阴位', '行狠', '了戾', '绝阴', '纯阳', '七鸟', '岁薄', '阴阳交破', '阴阳俱错', '阴阳击冲', '逐阵', '阳错阴冲', '七符', '天狗', '九虎', '成日', '天符', '孤阳', '绝阳', '纯阴', '六蛇', '阴神', '解除', '阳破阴冲');
    private static $DAY_SHEN_SHA = 'Truncated';
    public static $ZHI_TIAN_SHEN_OFFSET = array('子' => 4, '丑' => 2, '寅' => 0, '卯' => 10, '辰' => 8, '巳' => 6, '午' => 4, '未' => 2, '申' => 0, '酉' => 10, '戌' => 8, '亥' => 6);
    public static $TIAN_SHEN_TYPE = array('青龙' => '黄道', '明堂' => '黄道', '金匮' => '黄道', '天德' => '黄道', '玉堂' => '黄道', '司命' => '黄道', '天刑' => '黑道', '朱雀' => '黑道', '白虎' => '黑道', '天牢' => '黑道', '玄武' => '黑道', '勾陈' => '黑道');
    public static $TIAN_SHEN_TYPE_LUCK = array('黄道' => '吉', '黑道' => '凶');
    public static $LU = array('甲' => '寅', '乙' => '卯', '丙' => '巳', '丁' => '午', '戊' => '巳', '己' => '午', '庚' => '申', '辛' => '酉', '壬' => '亥', '癸' => '子', '寅' => '甲', '卯' => '乙', '巳' => '丙,戊', '午' => '丁,己', '申' => '庚', '酉' => '辛', '亥' => '壬', '子' => '癸');
    public static $PENG_ZU_GAN = array('', '甲不开仓财物耗散', '乙不栽植千株不长', '丙不修灶必见灾殃', '丁不剃头头必生疮', '戊不受田田主不祥', '己不破券二比并亡', '庚不经络织机虚张', '辛不合酱主人不尝', '壬不泱水更难提防', '癸不词讼理弱敌强');
    public static $PENG_ZU_ZHI = array('', '子不问卜自惹祸殃', '丑不冠带主不还乡', '寅不祭祀神鬼不尝', '卯不穿井水泉不香', '辰不哭泣必主重丧', '巳不远行财物伏藏', '午不苫盖屋主更张', '未不服药毒气入肠', '申不安床鬼祟入房', '酉不会客醉坐颠狂', '戌不吃犬作怪上床', '亥不嫁娶不利新郎');
    public static $NUMBER = array('〇', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '十二');
    public static $MONTH = array('', '正', '二', '三', '四', '五', '六', '七', '八', '九', '十', '冬', '腊');
    public static $SEASON = array('', '孟春', '仲春', '季春', '孟夏', '仲夏', '季夏', '孟秋', '仲秋', '季秋', '孟冬', '仲冬', '季冬');
    public static $SHENG_XIAO = array('', '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
    public static $DAY = array('', '初一', '初二', '初三', '初四', '初五', '初六', '初七', '初八', '初九', '初十', '十一', '十二', '十三', '十四', '十五', '十六', '十七', '十八', '十九', '二十', '廿一', '廿二', '廿三', '廿四', '廿五', '廿六', '廿七', '廿八', '廿九', '三十');
    public static $YUE_XIANG = array('', '朔', '既朔', '蛾眉新', '蛾眉新', '蛾眉', '夕', '上弦', '上弦', '九夜', '宵', '宵', '宵', '渐盈凸', '小望', '望', '既望', '立待', '居待', '寝待', '更待', '渐亏凸', '下弦', '下弦', '有明', '有明', '蛾眉残', '蛾眉残', '残', '晓', '晦');
    public static $FESTIVAL = array('1-1' => '春节', '1-15' => '元宵节', '2-2' => '龙头节', '5-5' => '端午节', '7-7' => '七夕节', '8-15' => '中秋节', '9-9' => '重阳节', '12-8' => '腊八节');
    public static $OTHER_FESTIVAL = array('1-4' => array('接神日'), '1-5' => array('隔开日'), '1-7' => array('人日'), '1-8' => array('谷日', '顺星节'), '1-9' => array('天日'), '1-10' => array('地日'), '1-20' => array('天穿节'), '1-25' => array('填仓节'), '1-30' => array('正月晦'), '2-1' => array('中和节'), '2-2' => array('社日节'), '3-3' => array('上巳节'), '5-20' => array('分龙节'), '5-25' => array('会龙节'), '6-6' => array('天贶节'), '6-24' => array('观莲节'), '6-25' => array('五谷母节'), '7-15' => array('中元节'), '7-22' => array('财神节'), '7-29' => array('地藏节'), '8-1' => array('天灸日'), '10-1' => array('寒衣节'), '10-10' => array('十成节'), '10-15' => array('下元节'), '12-7' => array('驱傩日'), '12-16' => array('尾牙'), '12-24' => array('祭灶日'));
    public static $XIU = array('申1' => '毕', '申2' => '翼', '申3' => '箕', '申4' => '奎', '申5' => '鬼', '申6' => '氐', '申0' => '虚', '子1' => '毕', '子2' => '翼', '子3' => '箕', '子4' => '奎', '子5' => '鬼', '子6' => '氐', '子0' => '虚', '辰1' => '毕', '辰2' => '翼', '辰3' => '箕', '辰4' => '奎', '辰5' => '鬼', '辰6' => '氐', '辰0' => '虚', '巳1' => '危', '巳2' => '觜', '巳3' => '轸', '巳4' => '斗', '巳5' => '娄', '巳6' => '柳', '巳0' => '房', '酉1' => '危', '酉2' => '觜', '酉3' => '轸', '酉4' => '斗', '酉5' => '娄', '酉6' => '柳', '酉0' => '房', '丑1' => '危', '丑2' => '觜', '丑3' => '轸', '丑4' => '斗', '丑5' => '娄', '丑6' => '柳', '丑0' => '房', '寅1' => '心', '寅2' => '室', '寅3' => '参', '寅4' => '角', '寅5' => '牛', '寅6' => '胃', '寅0' => '星', '午1' => '心', '午2' => '室', '午3' => '参', '午4' => '角', '午5' => '牛', '午6' => '胃', '午0' => '星', '戌1' => '心', '戌2' => '室', '戌3' => '参', '戌4' => '角', '戌5' => '牛', '戌6' => '胃', '戌0' => '星', '亥1' => '张', '亥2' => '尾', '亥3' => '壁', '亥4' => '井', '亥5' => '亢', '亥6' => '女', '亥0' => '昴', '卯1' => '张', '卯2' => '尾', '卯3' => '壁', '卯4' => '井', '卯5' => '亢', '卯6' => '女', '卯0' => '昴', '未1' => '张', '未2' => '尾', '未3' => '壁', '未4' => '井', '未5' => '亢', '未6' => '女', '未0' => '昴');
    public static $XIU_LUCK = array('角' => '吉', '亢' => '凶', '氐' => '凶', '房' => '吉', '心' => '凶', '尾' => '吉', '箕' => '吉', '斗' => '吉', '牛' => '凶', '女' => '凶', '虚' => '凶', '危' => '凶', '室' => '吉', '壁' => '吉', '奎' => '凶', '娄' => '吉', '胃' => '吉', '昴' => '凶', '毕' => '吉', '觜' => '凶', '参' => '吉', '井' => '吉', '鬼' => '凶', '柳' => '凶', '星' => '凶', '张' => '吉', '翼' => '凶', '轸' => '吉');
    public static $XIU_SONG = array('角' => '角星造作主荣昌，外进田财及女郎，嫁娶婚姻出贵子，文人及第见君王，惟有埋葬不可用，三年之后主瘟疫，起工修筑坟基地，堂前立见主人凶。', '亢' => '亢星造作长房当，十日之中主有殃，田地消磨官失职，接运定是虎狼伤，嫁娶婚姻用此日，儿孙新妇守空房，埋葬若还用此日，当时害祸主重伤。', '氐' => '氐星造作主灾凶，费尽田园仓库空，埋葬不可用此日，悬绳吊颈祸重重，若是婚姻离别散，夜招浪子入房中，行船必定遭沉没，更生聋哑子孙穷。', '房' => '房星造作田园进，钱财牛马遍山岗，更招外处田庄宅，荣华富贵福禄康，埋葬若然用此日，高官进职拜君王，嫁娶嫦娥至月殿，三年抱子至朝堂。', '心' => '心星造作大为凶，更遭刑讼狱囚中，忤逆官非宅产退，埋葬卒暴死相从，婚姻若是用此日，子死儿亡泪满胸，三年之内连遭祸，事事教君没始终。', '尾' => '尾星造作主天恩，富贵荣华福禄增，招财进宝兴家宅，和合婚姻贵子孙，埋葬若能依此日，男清女正子孙兴，开门放水招田宅，代代公侯远播名。', '箕' => '箕星造作主高强，岁岁年年大吉昌，埋葬修坟大吉利，田蚕牛马遍山岗，开门放水招田宅，箧满金银谷满仓，福荫高官加禄位，六亲丰禄乐安康。', '斗' => '斗星造作主招财，文武官员位鼎台，田宅家财千万进，坟堂修筑贵富来，开门放水招牛马，旺蚕男女主和谐，遇此吉宿来照护，时支福庆永无灾。', '牛' => '牛星造作主灾危，九横三灾不可推，家宅不安人口退，田蚕不利主人衰，嫁娶婚姻皆自损，金银财谷渐无之，若是开门并放水，牛猪羊马亦伤悲。', '女' => '女星造作损婆娘，兄弟相嫌似虎狼，埋葬生灾逢鬼怪，颠邪疾病主瘟惶，为事遭官财失散，泻利留连不可当，开门放水用此日，全家财散主离乡。', '虚' => '虚星造作主灾殃，男女孤眠不一双，内乱风声无礼节，儿孙媳妇伴人床，开门放水遭灾祸，虎咬蛇伤又卒亡，三三五五连年病，家破人亡不可当。', '危' => '危星不可造高楼，自遭刑吊见血光，三年孩子遭水厄，后生出外永不还，埋葬若还逢此日，周年百日取高堂，三年两载一悲伤，开门放水到官堂。', '室' => '室星修造进田牛，儿孙代代近王侯，家贵荣华天上至，寿如彭祖八千秋，开门放水招财帛，和合婚姻生贵儿，埋葬若能依此日，门庭兴旺福无休。', '壁' => '壁星造作主增财，丝蚕大熟福滔天，奴婢自来人口进，开门放水出英贤，埋葬招财官品进，家中诸事乐陶然，婚姻吉利主贵子，早播名誉著祖鞭。', '奎' => '奎星造作得祯祥，家内荣和大吉昌，若是埋葬阴卒死，当年定主两三伤，看看军令刑伤到，重重官事主瘟惶，开门放水遭灾祸，三年两次损儿郎。', '娄' => '娄星修造起门庭，财旺家和事事兴，外进钱财百日进，一家兄弟播高名，婚姻进益生贵子，玉帛金银箱满盈，放水开门皆吉利，男荣女贵寿康宁。', '胃' => '胃星造作事如何，家贵荣华喜气多，埋葬贵临官禄位，夫妇齐眉永保康，婚姻遇此家富贵，三灾九祸不逢他，从此门前多吉庆，儿孙代代拜金阶。', '昴' => '昴星造作进田牛，埋葬官灾不得休，重丧二日三人死，尽卖田园不记增，开门放水招灾祸，三岁孩儿白了头，婚姻不可逢此日，死别生离是可愁。', '毕' => '毕星造作主光前，买得田园有余钱，埋葬此日添官职，田蚕大熟永丰年，开门放水多吉庆，合家人口得安然，婚姻若得逢此日，生得孩儿福寿全。', '觜' => '觜星造作有徒刑，三年必定主伶丁，埋葬卒死多因此，取定寅年使杀人，三丧不止皆由此，一人药毒二人身，家门田地皆退败，仓库金银化作尘。', '参' => '参星造作旺人家，文星照耀大光华，只因造作田财旺，埋葬招疾哭黄沙，开门放水加官职，房房子孙见田加，婚姻许遁遭刑克，男女朝开幕落花。', '井' => '井星造作旺蚕田，金榜题名第一光，埋葬须防惊卒死，狂颠风疾入黄泉，开门放水招财帛，牛马猪羊旺莫言，贵人田塘来入宅，儿孙兴旺有余钱。', '鬼' => '鬼星起造卒人亡，堂前不见主人郎，埋葬此日官禄至，儿孙代代近君王，开门放水须伤死，嫁娶夫妻不久长，修土筑墙伤产女，手扶双女泪汪汪。', '柳' => '柳星造作主遭官，昼夜偷闭不暂安，埋葬瘟惶多疾病，田园退尽守冬寒，开门放水遭聋瞎，腰驼背曲似弓弯，更有棒刑宜谨慎，妇人随客走盘桓。', '星' => '星宿日好造新房，进职加官近帝王，不可埋葬并放水，凶星临位女人亡，生离死别无心恋，要自归休别嫁郎，孔子九曲殊难度，放水开门天命伤。', '张' => '张星日好造龙轩，年年并见进庄田，埋葬不久升官职，代代为官近帝前，开门放水招财帛，婚姻和合福绵绵，田蚕人满仓库满，百般顺意自安然。', '翼' => '翼星不利架高堂，三年二载见瘟惶，埋葬若还逢此日，子孙必定走他乡，婚姻此日不宜利，归家定是不相当，开门放水家须破，少女恋花贪外郎。', '轸' => '轸星临水造龙宫，代代为官受皇封，富贵荣华增寿禄，库满仓盈自昌隆，埋葬文昌来照助，宅舍安宁不见凶，更有为官沾帝宠，婚姻龙子入龙宫。');
    public static $SHOU = array('东' => '青龙', '南' => '朱雀', '西' => '白虎', '北' => '玄武');
    public static $CHONG = array('午', '未', '申', '酉', '戌', '亥', '子', '丑', '寅', '卯', '辰', '巳');
    public static $CHONG_GAN = array('戊', '己', '庚', '辛', '壬', '癸', '甲', '乙', '丙', '丁');
    public static $CHONG_GAN_TIE = array('己', '戊', '辛', '庚', '癸', '壬', '乙', '甲', '丁', '丙');
    public static $CHONG_GAN_4 = array('庚', '辛', '壬', '癸', '', '', '甲', '乙', '丙', '丁');
    public static $HE_GAN_5 = array('己', '庚', '辛', '壬', '癸', '甲', '乙', '丙', '丁', '戊');
    public static $HE_ZHI_6 = array('丑', '子', '亥', '戌', '酉', '申', '未', '午', '巳', '辰', '卯', '寅');
    public static $SHA = array('子' => '南', '丑' => '东', '寅' => '北', '卯' => '西', '辰' => '南', '巳' => '东', '午' => '北', '未' => '西', '申' => '南', '酉' => '东', '戌' => '北', '亥' => '西');
    public static $POSITION_DESC = array('坎' => '正北', '艮' => '东北', '震' => '正东', '巽' => '东南', '离' => '正南', '坤' => '西南', '兑' => '正西', '乾' => '西北', '中' => '中宫');
    public static $GONG = array('角' => '东', '井' => '南', '奎' => '西', '斗' => '北', '亢' => '东', '鬼' => '南', '娄' => '西', '牛' => '北', '氐' => '东', '柳' => '南', '胃' => '西', '女' => '北', '房' => '东', '星' => '南', '昴' => '西', '虚' => '北', '心' => '东', '张' => '南', '毕' => '西', '危' => '北', '尾' => '东', '翼' => '南', '觜' => '西', '室' => '北', '箕' => '东', '轸' => '南', '参' => '西', '壁' => '北');
    public static $ZHENG = array('角' => '木', '井' => '木', '奎' => '木', '斗' => '木', '亢' => '金', '鬼' => '金', '娄' => '金', '牛' => '金', '氐' => '土', '柳' => '土', '胃' => '土', '女' => '土', '房' => '日', '星' => '日', '昴' => '日', '虚' => '日', '心' => '月', '张' => '月', '毕' => '月', '危' => '月', '尾' => '火', '翼' => '火', '觜' => '火', '室' => '火', '箕' => '水', '轸' => '水', '参' => '水', '壁' => '水');
    public static $ANIMAL = array('角' => '蛟', '斗' => '獬', '奎' => '狼', '井' => '犴', '亢' => '龙', '牛' => '牛', '娄' => '狗', '鬼' => '羊', '女' => '蝠', '氐' => '貉', '胃' => '彘', '柳' => '獐', '房' => '兔', '虚' => '鼠', '昴' => '鸡', '星' => '马', '心' => '狐', '危' => '燕', '毕' => '乌', '张' => '鹿', '尾' => '虎', '室' => '猪', '觜' => '猴', '翼' => '蛇', '箕' => '豹', '壁' => '獝', '参' => '猿', '轸' => '蚓');
    public static $WU_XING_GAN = array('甲' => '木', '乙' => '木', '丙' => '火', '丁' => '火', '戊' => '土', '己' => '土', '庚' => '金', '辛' => '金', '壬' => '水', '癸' => '水');
    public static $WU_XING_ZHI = array('寅' => '木', '卯' => '木', '巳' => '火', '午' => '火', '辰' => '土', '丑' => '土', '戌' => '土', '未' => '土', '申' => '金', '酉' => '金', '亥' => '水', '子' => '水');
    public static $NAYIN = array('甲子' => '海中金', '甲午' => '沙中金', '丙寅' => '炉中火', '丙申' => '山下火', '戊辰' => '大林木', '戊戌' => '平地木', '庚午' => '路旁土', '庚子' => '壁上土', '壬申' => '剑锋金', '壬寅' => '金箔金', '甲戌' => '山头火', '甲辰' => '覆灯火', '丙子' => '涧下水', '丙午' => '天河水', '戊寅' => '城头土', '戊申' => '大驿土', '庚辰' => '白蜡金', '庚戌' => '钗钏金', '壬午' => '杨柳木', '壬子' => '桑柘木', '甲申' => '泉中水', '甲寅' => '大溪水', '丙戌' => '屋上土', '丙辰' => '沙中土', '戊子' => '霹雳火', '戊午' => '天上火', '庚寅' => '松柏木', '庚申' => '石榴木', '壬辰' => '长流水', '壬戌' => '大海水', '乙丑' => '海中金', '乙未' => '沙中金', '丁卯' => '炉中火', '丁酉' => '山下火', '己巳' => '大林木', '己亥' => '平地木', '辛未' => '路旁土', '辛丑' => '壁上土', '癸酉' => '剑锋金', '癸卯' => '金箔金', '乙亥' => '山头火', '乙巳' => '覆灯火', '丁丑' => '涧下水', '丁未' => '天河水', '己卯' => '城头土', '己酉' => '大驿土', '辛巳' => '白蜡金', '辛亥' => '钗钏金', '癸未' => '杨柳木', '癸丑' => '桑柘木', '乙酉' => '泉中水', '乙卯' => '大溪水', '丁亥' => '屋上土', '丁巳' => '沙中土', '己丑' => '霹雳火', '己未' => '天上火', '辛卯' => '松柏木', '辛酉' => '石榴木', '癸巳' => '长流水', '癸亥' => '大海水');
    public static $SHI_SHEN = array('甲甲' => '比肩', '甲乙' => '劫财', '甲丙' => '食神', '甲丁' => '伤官', '甲戊' => '偏财', '甲己' => '正财', '甲庚' => '七杀', '甲辛' => '正官', '甲壬' => '偏印', '甲癸' => '正印', '乙乙' => '比肩', '乙甲' => '劫财', '乙丁' => '食神', '乙丙' => '伤官', '乙己' => '偏财', '乙戊' => '正财', '乙辛' => '七杀', '乙庚' => '正官', '乙癸' => '偏印', '乙壬' => '正印', '丙丙' => '比肩', '丙丁' => '劫财', '丙戊' => '食神', '丙己' => '伤官', '丙庚' => '偏财', '丙辛' => '正财', '丙壬' => '七杀', '丙癸' => '正官', '丙甲' => '偏印', '丙乙' => '正印', '丁丁' => '比肩', '丁丙' => '劫财', '丁己' => '食神', '丁戊' => '伤官', '丁辛' => '偏财', '丁庚' => '正财', '丁癸' => '七杀', '丁壬' => '正官', '丁乙' => '偏印', '丁甲' => '正印', '戊戊' => '比肩', '戊己' => '劫财', '戊庚' => '食神', '戊辛' => '伤官', '戊壬' => '偏财', '戊癸' => '正财', '戊甲' => '七杀', '戊乙' => '正官', '戊丙' => '偏印', '戊丁' => '正印', '己己' => '比肩', '己戊' => '劫财', '己辛' => '食神', '己庚' => '伤官', '己癸' => '偏财', '己壬' => '正财', '己乙' => '七杀', '己甲' => '正官', '己丁' => '偏印', '己丙' => '正印', '庚庚' => '比肩', '庚辛' => '劫财', '庚壬' => '食神', '庚癸' => '伤官', '庚甲' => '偏财', '庚乙' => '正财', '庚丙' => '七杀', '庚丁' => '正官', '庚戊' => '偏印', '庚己' => '正印', '辛辛' => '比肩', '辛庚' => '劫财', '辛癸' => '食神', '辛壬' => '伤官', '辛乙' => '偏财', '辛甲' => '正财', '辛丁' => '七杀', '辛丙' => '正官', '辛己' => '偏印', '辛戊' => '正印', '壬壬' => '比肩', '壬癸' => '劫财', '壬甲' => '食神', '壬乙' => '伤官', '壬丙' => '偏财', '壬丁' => '正财', '壬戊' => '七杀', '壬己' => '正官', '壬庚' => '偏印', '壬辛' => '正印', '癸癸' => '比肩', '癸壬' => '劫财', '癸乙' => '食神', '癸甲' => '伤官', '癸丁' => '偏财', '癸丙' => '正财', '癸己' => '七杀', '癸戊' => '正官', '癸辛' => '偏印', '癸庚' => '正印');
    public static $ZHI_HIDE_GAN = array('子' => array('癸'), '丑' => array('己', '癸', '辛'), '寅' => array('甲', '丙', '戊'), '卯' => array('乙'), '辰' => array('戊', '乙', '癸'), '巳' => array('丙', '庚', '戊'), '午' => array('丁', '己'), '未' => array('己', '丁', '乙'), '申' => array('庚', '壬', '戊'), '酉' => array('辛'), '戌' => array('戊', '辛', '丁'), '亥' => array('壬', '甲'));
    public static function getTimeZhiIndex($hm)
    {
        if (null == $hm) {
            return 0;
        }
        if (strlen($hm) > 5) {
            $hm = substr($hm, 0, 5);
        }
        $x = 1;
        for ($i = 1; $i < 22; $i += 2) {
            if (strcmp($hm, ($i < 10 ? '0' : '') . $i . ':00') >= 0 && strcmp($hm, ($i + 1 < 10 ? '0' : '') . ($i + 1) . ':59') <= 0) {
                return $x;
            }
            $x++;
        }
        return 0;
    }
    public static function convertTime($hm)
    {
        return self::$ZHI[self::getTimeZhiIndex($hm) + 1];
    }
    private static function hex($n)
    {
        $s = dechex($n);
        if (strlen($s) < 2) {
            $s = '0' . $s;
        }
        return strtoupper($s);
    }
    public static function index($name, $names, $offset)
    {
        for ($i = 0, $j = count($names); $i < $j; $i++) {
            if (strcmp($names[$i], $name) === 0) {
                return $i + $offset;
            }
        }
        return -1;
    }
    public static function getJiaZiIndex($ganZhi)
    {
        return self::index($ganZhi, self::$JIA_ZI, 0);
    }
    public static function getDayYi($monthGanZhi, $dayGanZhi)
    {
        $l = array();
        $day = self::hex(self::getJiaZiIndex($dayGanZhi));
        $month = self::hex(self::getJiaZiIndex($monthGanZhi));
        $right = self::$DAY_YI_JI;
        $index = strpos($right, $day . '=');
        while ($index > -1) {
            $right = substr($right, $index + 3);
            $left = $right;
            if (strpos($left, '=') !== false) {
                $left = substr($left, 0, strpos($left, '=') - 2);
            }
            $matched = false;
            $months = substr($left, 0, strpos($left, ':'));
            for ($i = 0, $j = strlen($months); $i < $j; $i += 2) {
                $m = substr($months, $i, 2);
                if ($m == $month) {
                    $matched = true;
                    break;
                }
            }
            if ($matched) {
                $ys = substr($left, strpos($left, ':') + 1, strlen($left));
                $ys = substr($ys, 0, strpos($ys, ','));
                for ($i = 0, $j = strlen($ys); $i < $j; $i += 2) {
                    $l[] = self::$YI_JI[hexdec(substr($ys, $i, 2))];
                }
                break;
            }
            $index = strpos($right, $day . '=');
        }
        if (count($l) < 1) {
            $l[] = '无';
        }
        return $l;
    }
    public static function getDayJi($monthGanZhi, $dayGanZhi)
    {
        $l = array();
        $day = self::hex(self::getJiaZiIndex($dayGanZhi));
        $month = self::hex(self::getJiaZiIndex($monthGanZhi));
        $right = self::$DAY_YI_JI;
        $index = strpos($right, $day . '=');
        while ($index > -1) {
            $right = substr($right, $index + 3);
            $left = $right;
            if (strpos($left, '=') !== false) {
                $left = substr($left, 0, strpos($left, '=') - 2);
            }
            $matched = false;
            $months = substr($left, 0, strpos($left, ':'));
            for ($i = 0, $j = strlen($months); $i < $j; $i += 2) {
                $m = substr($months, $i, 2);
                if ($m == $month) {
                    $matched = true;
                    break;
                }
            }
            if ($matched) {
                $ys = substr($left, strpos($left, ',') + 1, strlen($left));
                for ($i = 0, $j = strlen($ys); $i < $j; $i += 2) {
                    $l[] = self::$YI_JI[hexdec(substr($ys, $i, 2))];
                }
                break;
            }
            $index = strpos($right, $day . '=');
        }
        if (count($l) < 1) {
            $l[] = '无';
        }
        return $l;
    }
    public static function getDayJiShen($lunarMonth, $dayGanZhi)
    {
        $l = array();
        $day = self::hex(self::getJiaZiIndex($dayGanZhi));
        $month = strtoupper(dechex(abs($lunarMonth)));
        $index = strpos(self::$DAY_SHEN_SHA, $month . $day . '=');
        if ($index > -1) {
            $left = substr(self::$DAY_SHEN_SHA, $index + 4);
            if (strpos($left, '=') !== false) {
                $left = substr($left, 0, strpos($left, '=') - 3);
            }
            $js = substr($left, 0, strpos($left, ','));
            for ($i = 0, $j = strlen($js); $i < $j; $i += 2) {
                $l[] = self::$SHEN_SHA[hexdec(substr($js, $i, 2))];
            }
        }
        if (count($l) < 1) {
            $l[] = '无';
        }
        return $l;
    }
    public static function getDayXiongSha($lunarMonth, $dayGanZhi)
    {
        $l = array();
        $day = self::hex(self::getJiaZiIndex($dayGanZhi));
        $month = strtoupper(dechex(abs($lunarMonth)));
        $index = strpos(self::$DAY_SHEN_SHA, $month . $day . '=');
        if ($index > -1) {
            $left = substr(self::$DAY_SHEN_SHA, $index + 4);
            if (strpos($left, '=') !== false) {
                $left = substr($left, 0, strpos($left, '=') - 3);
            }
            $xs = substr($left, strpos($left, ',') + 1, strlen($left));
            for ($i = 0, $j = strlen($xs); $i < $j; $i += 2) {
                $l[] = self::$SHEN_SHA[hexdec(substr($xs, $i, 2))];
            }
        }
        if (count($l) < 1) {
            $l[] = '无';
        }
        return $l;
    }
    public static function getTimeYi($dayGanZhi, $timeGanZhi)
    {
        $l = array();
        $day = self::hex(self::getJiaZiIndex($dayGanZhi));
        $time = self::hex(self::getJiaZiIndex($timeGanZhi));
        $index = strpos(self::$TIME_YI_JI, $day . $time . '=');
        if ($index > -1) {
            $left = substr(self::$TIME_YI_JI, $index + 5);
            if (strpos($left, '=') !== false) {
                $left = substr($left, 0, strpos($left, '=') - 4);
            }
            $ys = substr($left, 0, strpos($left, ','));
            for ($i = 0, $j = strlen($ys); $i < $j; $i += 2) {
                $l[] = self::$YI_JI[hexdec(substr($ys, $i, 2))];
            }
        }
        if (count($l) < 1) {
            $l[] = '无';
        }
        return $l;
    }
    public static function getTimeJi($dayGanZhi, $timeGanZhi)
    {
        $l = array();
        $day = self::hex(self::getJiaZiIndex($dayGanZhi));
        $time = self::hex(self::getJiaZiIndex($timeGanZhi));
        $index = strpos(self::$TIME_YI_JI, $day . $time . '=');
        if ($index > -1) {
            $left = substr(self::$TIME_YI_JI, $index + 5);
            if (strpos($left, '=') !== false) {
                $left = substr($left, 0, strpos($left, '=') - 4);
            }
            $js = substr($left, strpos($left, ',') + 1, strlen($left));
            for ($i = 0, $j = strlen($js); $i < $j; $i += 2) {
                $l[] = self::$YI_JI[hexdec(substr($js, $i, 2))];
            }
        }
        if (count($l) < 1) {
            $l[] = '无';
        }
        return $l;
    }
    private static function getXunIndex($ganZhi)
    {
        $half = strlen($ganZhi) / 2;
        $gan = substr($ganZhi, 0, $half);
        $zhi = substr($ganZhi, $half);
        $ganIndex = 0;
        $zhiIndex = 0;
        for ($i = 0, $j = count(self::$GAN); $i < $j; $i++) {
            if (strcmp(self::$GAN[$i], $gan) === 0) {
                $ganIndex = $i;
                break;
            }
        }
        for ($i = 0, $j = count(self::$ZHI); $i < $j; $i++) {
            if (strcmp(self::$ZHI[$i], $zhi) === 0) {
                $zhiIndex = $i;
                break;
            }
        }
        $diff = $ganIndex - $zhiIndex;
        if ($diff < 0) {
            $diff += 12;
        }
        return $diff / 2;
    }
    public static function getXun($ganZhi)
    {
        return self::$XUN[self::getXunIndex($ganZhi)];
    }
    public static function getXunKong($ganZhi)
    {
        return self::$XUN_KONG[self::getXunIndex($ganZhi)];
    }
}
class ShouXingUtil
{
    public static $ONE_THIRD = 0.3333333333333333;
    public static $DAY_PER_YEAR = 365.2422;
    public static $SECOND_PER_DAY = 86400;
    public static $SECOND_PER_RAD = 206264.80624709636;
    private static $NUT_B = array(2.1824, -33.75705, 36e-6, -1720, 920, 3.5069, 1256.66393, 11e-6, -132, 57, 1.3375, 16799.4182, -51e-6, -23, 10, 4.3649, -67.5141, 72e-6, 21, -9, 0.04, -628.302, 0, -14, 0, 2.36, 8328.691, 0, 7, 0, 3.46, 1884.966, 0, -5, 2, 5.44, 16833.175, 0, -4, 2, 3.69, 25128.110, 0, -3, 0, 3.55, 628.362, 0, 2, 0);
    private static $DT_AT = array(-4000, 108371.7, -13036.80, 392.000, 0.0000, -500, 17201.0, -627.82, 16.170, -0.3413, -150, 12200.6, -346.41, 5.403, -0.1593, 150, 9113.8, -328.13, -1.647, 0.0377, 500, 5707.5, -391.41, 0.915, 0.3145, 900, 2203.4, -283.45, 13.034, -0.1778, 1300, 490.1, -57.35, 2.085, -0.0072, 1600, 120.0, -9.81, -1.532, 0.1403, 1700, 10.2, -0.91, 0.510, -0.0370, 1800, 13.4, -0.72, 0.202, -0.0193, 1830, 7.8, -1.81, 0.416, -0.0247, 1860, 8.3, -0.13, -0.406, 0.0292, 1880, -5.4, 0.32, -0.183, 0.0173, 1900, -2.3, 2.06, 0.169, -0.0135, 1920, 21.2, 1.69, -0.304, 0.0167, 1940, 24.2, 1.22, -0.064, 0.0031, 1960, 33.2, 0.51, 0.231, -0.0109, 1980, 51.0, 1.29, -0.026, 0.0032, 2000, 63.87, 0.1, 0, 0, 2005, 64.7, 0.21, 0, 0, 2012, 66.8, 0.22, 0, 0, 2018, 73.6, 0.40, 0, 0, 2021, 78.1, 0.44, 0, 0, 2024, 83.1, 0.55, 0, 0, 2028, 98.6);
    private static $XL0 = array(10000000000,);
    private static $XL1 = array(array(22639.586, 0.78475822, 0, 0, 0));
    private static $QI_KB = array(1640650.479938,);
    private static $SHUO_KB = array(1457698.231017, 29.53067166, 1546082.512234, 29.53085106, 1640640.735300, 29.53060000, 1642472.151543, 29.53085439, 1683430.509300, 29.53086148, 1752148.041079, 29.53085097, 1807665.420323, 29.53059851, 1883618.114100, 29.53060000, 1907360.704700, 29.53060000, 1936596.224900, 29.53060000, 1939135.675300, 29.53060000, 1947168.00);
    private static $QB;
    private static $SB;
    private static function decode($s)
    {
        $o = '0000000000';
        $o2 = $o . $o;
        $s = str_replace('J', '00', $s);
        $s = str_replace('I', '000', $s);
        $s = str_replace('H', '0000', $s);
        $s = str_replace('G', '00000', $s);
        $s = str_replace('t', '02', $s);
        $s = str_replace('s', '002', $s);
        $s = str_replace('r', '0002', $s);
        $s = str_replace('q', '00002', $s);
        $s = str_replace('p', '000002', $s);
        $s = str_replace('o', '0000002', $s);
        $s = str_replace('n', '00000002', $s);
        $s = str_replace('m', '000000002', $s);
        $s = str_replace('l', '0000000002', $s);
        $s = str_replace('k', '01', $s);
        $s = str_replace('j', '0101', $s);
        $s = str_replace('i', '001', $s);
        $s = str_replace('h', '001001', $s);
        $s = str_replace('g', '0001', $s);
        $s = str_replace('f', '00001', $s);
        $s = str_replace('e', '000001', $s);
        $s = str_replace('d', '0000001', $s);
        $s = str_replace('c', '00000001', $s);
        $s = str_replace('b', '000000001', $s);
        $s = str_replace('a', '0000000001', $s);
        $s = str_replace('A', $o2 . $o2 . $o2, $s);
        $s = str_replace('B', $o2 . $o2 . $o, $s);
        $s = str_replace('C', $o2 . $o2, $s);
        $s = str_replace('D', $o2 . $o, $s);
        $s = str_replace('E', $o2, $s);
        return str_replace('F', $o, $s);
    }
    public static function nutationLon2($t)
    {
        $a = -1.742 * $t;
        $t2 = $t * $t;
        $dl = 0;
        for ($i = 0, $j = count(self::$NUT_B); $i < $j; $i += 5) {
            $dl += (self::$NUT_B[$i + 3] + $a) * sin(self::$NUT_B[$i] + self::$NUT_B[$i + 1] * $t + self::$NUT_B[$i + 2] * $t2);
            $a = 0;
        }
        return $dl / 100 / self::$SECOND_PER_RAD;
    }
    public static function eLon($t, $n)
    {
        $t /= 10;
        $v = 0;
        $tn = 1;
        $pn = 1;
        $m0 = self::$XL0[$pn + 1] - self::$XL0[$pn];
        for ($i = 0; $i < 6; $i++, $tn *= $t) {
            $n1 = (int)(self::$XL0[$pn + $i]);
            $n2 = (int)(self::$XL0[$pn + 1 + $i]);
            $n0 = $n2 - $n1;
            if ($n0 == 0) {
                continue;
            }
            if ($n < 0) {
                $m = $n2;
            } else {
                $m = (int)(3 * $n * $n0 / $m0 + 0.5) + $n1;
                if ($i != 0) {
                    $m += 3;
                }
                if ($m > $n2) {
                    $m = $n2;
                }
            }
            $c = 0;
            for ($j = $n1; $j < $m; $j += 3) {
                $c += self::$XL0[$j] * cos(self::$XL0[$j + 1] + $t * self::$XL0[$j + 2]);
            }
            $v += $c * $tn;
        }
        $v /= self::$XL0[0];
        $t2 = $t * $t;
        $v += (-0.0728 - 2.7702 * $t - 1.1019 * $t2 - 0.0996 * $t2 * $t) / self::$SECOND_PER_RAD;
        return $v;
    }
    public static function mLon($t, $n)
    {
        $ob = self::$XL1;
        $obl = count($ob[0]);
        $tn = 1;
        $v = 0;
        $t2 = $t * $t;
        $t3 = $t2 * $t;
        $t4 = $t3 * $t;
        $t5 = $t4 * $t;
        $tx = $t - 10;
        $v += (3.81034409 + 8399.684730072 * $t - 3.319e-05 * $t2 + 3.11e-08 * $t3 - 2.033e-10 * $t4) * self::$SECOND_PER_RAD;
        $v += 5028.792262 * $t + 1.1124406 * $t2 + 0.00007699 * $t3 - 0.000023479 * $t4 - 0.0000000178 * $t5;
        if ($tx > 0) {
            $v += -0.866 + 1.43 * $tx + 0.054 * $tx * $tx;
        }
        $t2 /= 1e4;
        $t3 /= 1e8;
        $t4 /= 1e8;
        $n *= 6;
        if ($n < 0) {
            $n = $obl;
        }
        for ($i = 0, $x = count($ob); $i < $x; $i++, $tn *= $t) {
            $f = $ob[$i];
            $l = count($f);
            $m = (int)($n * $l / $obl + 0.5);
            if ($i > 0) {
                $m += 6;
            }
            if ($m >= $l) {
                $m = $l;
            }
            for ($j = 0, $c = 0; $j < $m; $j += 6) {
                $c += $f[$j] * cos($f[$j + 1] + $t * $f[$j + 2] + $t2 * $f[$j + 3] + $t3 * $f[$j + 4] + $t4 * $f[$j + 5]);
            }
            $v += $c * $tn;
        }
        $v /= self::$SECOND_PER_RAD;
        return $v;
    }
    public static function gxcSunLon($t)
    {
        $t2 = $t * $t;
        $v = -0.043126 + 628.301955 * $t - 0.000002732 * $t2;
        $e = 0.016708634 - 0.000042037 * $t - 0.0000001267 * $t2;
        return -20.49552 * (1 + $e * cos($v)) / self::$SECOND_PER_RAD;
    }
    public static function ev($t)
    {
        $f = 628.307585 * $t;
        return 628.332 + 21 * sin(1.527 + $f) + 0.44 * sin(1.48 + $f * 2) + 0.129 * sin(5.82 + $f) * $t + 0.00055 * sin(4.21 + $f) * $t * $t;
    }
    public static function saLon($t, $n)
    {
        return self::eLon($t, $n) + self::nutationLon2($t) + self::gxcSunLon($t) + M_PI;
    }
    public static function dtExt($y, $jsd)
    {
        $dy = ($y - 1820) / 100;
        return -20 + $jsd * $dy * $dy;
    }
    public static function dtCalc($y)
    {
        $size = count(self::$DT_AT);
        $y0 = self::$DT_AT[$size - 2];
        $t0 = self::$DT_AT[$size - 1];
        if ($y >= $y0) {
            $jsd = 31;
            if ($y > $y0 + 100) {
                return self::dtExt($y, $jsd);
            }
            return self::dtExt($y, $jsd) - (self::dtExt($y0, $jsd) - $t0) * ($y0 + 100 - $y) / 100;
        }
        for ($i = 0; $i < $size; $i += 5) {
            if ($y < self::$DT_AT[$i + 5]) {
                break;
            }
        }
        $t1 = ($y - self::$DT_AT[$i]) / (self::$DT_AT[$i + 5] - self::$DT_AT[$i]) * 10;
        $t2 = $t1 * $t1;
        $t3 = $t2 * $t1;
        return self::$DT_AT[$i + 1] + self::$DT_AT[$i + 2] * $t1 + self::$DT_AT[$i + 3] * $t2 + self::$DT_AT[$i + 4] * $t3;
    }
    public static function dtT($t)
    {
        return self::dtCalc($t / 365.2425 + 2000) / self::$SECOND_PER_DAY;
    }
    public static function mv($t)
    {
        $v = 8399.71 - 914 * sin(0.7848 + 8328.691425 * $t + 0.0001523 * $t * $t);
        $v -= 179 * sin(2.543 + 15542.7543 * $t) + 160 * sin(0.1874 + 7214.0629 * $t) + 62 * sin(3.14 + 16657.3828 * $t) + 34 * sin(4.827 + 16866.9323 * $t) + 22 * sin(4.9 + 23871.4457 * $t) + 12 * sin(2.59 + 14914.4523 * $t) + 7 * sin(0.23 + 6585.7609 * $t) + 5 * sin(0.9 + 25195.624 * $t) + 5 * sin(2.32 - 7700.3895 * $t) + 5 * sin(3.88 + 8956.9934 * $t) + 5 * sin(0.49 + 7771.3771 * $t);
        return $v;
    }
    public static function saLonT($w)
    {
        $v = 628.3319653318;
        $t = ($w - 1.75347 - M_PI) / $v;
        $v = self::ev($t);
        $t += ($w - self::saLon($t, 10)) / $v;
        $v = self::ev($t);
        $t += ($w - self::saLon($t, -1)) / $v;
        return $t;
    }
    public static function saLonT2($w)
    {
        $v = 628.3319653318;
        $t = ($w - 1.75347 - M_PI) / $v;
        $t -= (0.000005297 * $t * $t + 0.0334166 * cos(4.669257 + 628.307585 * $t) + 0.0002061 * cos(2.67823 + 628.307585 * $t) * $t) / $v;
        $t += ($w - self::eLon($t, 8) - M_PI + (20.5 + 17.2 * sin(2.1824 - 33.75705 * $t)) / self::$SECOND_PER_RAD) / $v;
        return $t;
    }
    public static function msaLon($t, $mn, $sn)
    {
        return self::mLon($t, $mn) + (-3.4E-6) - (self::eLon($t, $sn) + self::gxcSunLon($t) + M_PI);
    }
    public static function msaLonT($w)
    {
        $v = 7771.37714500204;
        $t = ($w + 1.08472) / $v;
        $t += ($w - self::msaLon($t, 3, 3)) / $v;
        $v = self::mv($t) - self::ev($t);
        $t += ($w - self::msaLon($t, 20, 10)) / $v;
        $t += ($w - self::msaLon($t, -1, 60)) / $v;
        return $t;
    }
    public static function msaLonT2($w)
    {
        $v = 7771.37714500204;
        $t = ($w + 1.08472) / $v;
        $t2 = $t * $t;
        $t -= (-0.00003309 * $t2 + 0.10976 * cos(0.784758 + 8328.6914246 * $t + 0.000152292 * $t2) + 0.02224 * cos(0.18740 + 7214.0628654 * $t - 0.00021848 * $t2) - 0.03342 * cos(4.669257 + 628.307585 * $t)) / $v;
        $l = self::mLon($t, 20) - (4.8950632 + 628.3319653318 * $t + 0.000005297 * $t * $t + 0.0334166 * cos(4.669257 + 628.307585 * $t) + 0.0002061 * cos(2.67823 + 628.307585 * $t) * $t + 0.000349 * cos(4.6261 + 1256.61517 * $t) - 20.5 / self::$SECOND_PER_RAD);
        $v = 7771.38 - 914 * sin(0.7848 + 8328.691425 * $t + 0.0001523 * $t * $t) - 179 * sin(2.543 + 15542.7543 * $t) - 160 * sin(0.1874 + 7214.0629 * $t);
        $t += ($w - $l) / $v;
        return $t;
    }
    public static function qiHigh($w)
    {
        $t = self::saLonT2($w) * 36525;
        $t = $t - self::dtT($t) + self::$ONE_THIRD;
        $v = (intval($t + 0.5) % 1) * self::$SECOND_PER_DAY;
        if ($v < 1200 || $v > self::$SECOND_PER_DAY - 1200) {
            $t = self::saLonT($w) * 36525 - self::dtT($t) + self::$ONE_THIRD;
        }
        return $t;
    }
    public static function shuoHigh($w)
    {
        $t = self::msaLonT2($w) * 36525;
        $t = $t - self::dtT($t) + self::$ONE_THIRD;
        $v = (intval($t + 0.5) % 1) * self::$SECOND_PER_DAY;
        if ($v < 1800 || $v > self::$SECOND_PER_DAY - 1800) {
            $t = self::msaLont($w) * 36525 - self::dtT($t) + self::$ONE_THIRD;
        }
        return $t;
    }
    public static function qiLow($w)
    {
        $v = 628.3319653318;
        $t = ($w - 4.895062166) / $v;
        $t -= (53 * $t * $t + 334116 * cos(4.67 + 628.307585 * $t) + 2061 * cos(2.678 + 628.3076 * $t) * $t) / $v / 10000000;
        $n = 48950621.66 + 6283319653.318 * $t + 53 * $t * $t + 334166 * cos(4.669257 + 628.307585 * $t) + 3489 * cos(4.6261 + 1256.61517 * $t) + 2060.6 * cos(2.67823 + 628.307585 * $t) * $t - 994 - 834 * sin(2.1824 - 33.75705 * $t);
        $t -= ($n / 10000000 - $w) / 628.332 + (32 * ($t + 1.8) * ($t + 1.8) - 20) / self::$SECOND_PER_DAY / 36525;
        return $t * 36525 + self::$ONE_THIRD;
    }
    public static function shuoLow($w)
    {
        $v = 7771.37714500204;
        $t = ($w + 1.08472) / $v;
        $t -= (-0.0000331 * $t * $t + 0.10976 * cos(0.785 + 8328.6914 * $t) + 0.02224 * cos(0.187 + 7214.0629 * $t) - 0.03342 * cos(4.669 + 628.3076 * $t)) / $v + (32 * ($t + 1.8) * ($t + 1.8) - 20) / self::$SECOND_PER_DAY / 36525;
        return $t * 36525 + self::$ONE_THIRD;
    }
    public static function calcShuo($jd)
    {
        if (null == self::$SB) {
            self::$SB = self::decode('EqoFscDcrFpmEsF2DfFideFelFpFfFfFiaipqti1ksttikptikqckstekqttgkqttgkqteksttikptikq2fjstgjqttjkqttgkqtekstfkptikq2tijstgjiFkirFsAeACoFsiDaDiADc1AFbBfgdfikijFifegF1FhaikgFag1E2btaieeibggiffdeigFfqDfaiBkF1kEaikhkigeidhhdiegcFfakF1ggkidbiaedksaFffckekidhhdhdikcikiakicjF1deedFhFccgicdekgiFbiaikcfi1kbFibefgEgFdcFkFeFkdcfkF1kfkcickEiFkDacFiEfbiaejcFfffkhkdgkaiei1ehigikhdFikfckF1dhhdikcfgjikhfjicjicgiehdikcikggcifgiejF1jkieFhegikggcikFegiegkfjebhigikggcikdgkaFkijcfkcikfkcifikiggkaeeigefkcdfcfkhkdgkegieidhijcFfakhfgeidieidiegikhfkfckfcjbdehdikggikgkfkicjicjF1dbidikFiggcifgiejkiegkigcdiegfggcikdbgfgefjF1kfegikggcikdgFkeeijcfkcikfkekcikdgkabhkFikaffcfkhkdgkegbiaekfkiakicjhfgqdq2fkiakgkfkhfkfcjiekgFebicggbedF1jikejbbbiakgbgkacgiejkijjgigfiakggfggcibFifjefjF1kfekdgjcibFeFkijcfkfhkfkeaieigekgbhkfikidfcjeaibgekgdkiffiffkiakF1jhbakgdki1dj1ikfkicjicjieeFkgdkicggkighdF1jfgkgfgbdkicggfggkidFkiekgijkeigfiskiggfaidheigF1jekijcikickiggkidhhdbgcfkFikikhkigeidieFikggikhkffaffijhidhhakgdkhkijF1kiakF1kfheakgdkifiggkigicjiejkieedikgdfcggkigieeiejfgkgkigbgikicggkiaideeijkefjeijikhkiggkiaidheigcikaikffikijgkiahi1hhdikgjfifaakekighie1hiaikggikhkffakicjhiahaikggikhkijF1kfejfeFhidikggiffiggkigicjiekgieeigikggiffiggkidheigkgfjkeigiegikifiggkidhedeijcfkFikikhkiggkidhh1ehigcikaffkhkiggkidhh1hhigikekfiFkFikcidhh1hitcikggikhkfkicjicghiediaikggikhkijbjfejfeFhaikggifikiggkigiejkikgkgieeigikggiffiggkigieeigekijcijikggifikiggkideedeijkefkfckikhkiggkidhh1ehijcikaffkhkiggkidhh1hhigikhkikFikfckcidhh1hiaikgjikhfjicjicgiehdikcikggifikigiejfejkieFhegikggifikiggfghigkfjeijkhigikggifikiggkigieeijcijcikfksikifikiggkidehdeijcfdckikhkiggkhghh1ehijikifffffkhsFngErD1pAfBoDd1BlEtFqA2AqoEpDqElAEsEeB2BmADlDkqBtC1FnEpDqnEmFsFsAFnllBbFmDsDiCtDmAB2BmtCgpEplCpAEiBiEoFqFtEqsDcCnFtADnFlEgdkEgmEtEsCtDmADqFtAFrAtEcCqAE1BoFqC1F1DrFtBmFtAC2ACnFaoCgADcADcCcFfoFtDlAFgmFqBq2bpEoAEmkqnEeCtAE1bAEqgDfFfCrgEcBrACfAAABqAAB1AAClEnFeCtCgAADqDoBmtAAACbFiAAADsEtBqAB2FsDqpFqEmFsCeDtFlCeDtoEpClEqAAFrAFoCgFmFsFqEnAEcCqFeCtFtEnAEeFtAAEkFnErAABbFkADnAAeCtFeAfBoAEpFtAABtFqAApDcCGJ');
        }
        $size = count(self::$SHUO_KB);
        $d = 0;
        $pc = 14;
        $jd += Solar::$J2000;
        $f1 = self::$SHUO_KB[0] - $pc;
        $f2 = self::$SHUO_KB[$size - 1] - $pc;
        $f3 = 2436935;
        if ($jd < $f1 || $jd >= $f3) {
            $d = floor(self::shuoHigh(floor(($jd + $pc - 2451551) / 29.5306) * M_PI * 2) + 0.5);
        } else if ($jd >= $f1 && $jd < $f2) {
            for ($i = 0; $i < $size; $i += 2) {
                if ($jd + $pc < self::$SHUO_KB[$i + 2]) {
                    break;
                }
            }
            $d = self::$SHUO_KB[$i] + self::$SHUO_KB[$i + 1] * floor(($jd + $pc - self::$SHUO_KB[$i]) / self::$SHUO_KB[$i + 1]);
            $d = floor($d + 0.5);
            if ($d == 1683460) {
                $d++;
            }
            $d -= Solar::$J2000;
        } else if ($jd >= $f2 && $jd < $f3) {
            $d = floor(self::shuoLow(floor(($jd + $pc - 2451551) / 29.5306) * M_PI * 2) + 0.5);
            $from = (int)(($jd - $f2) / 29.5306);
            $n = substr(self::$SB, $from, 1);
            if (strcmp('1', $n) == 0) {
                $d += 1;
            } elseif (strcmp('2', $n) == 0) {
                $d -= 1;
            }
        }
        return $d;
    }
    public static function calcQi($jd)
    {
        if (null == self::$QB) {
            self::$QB = self::decode('FrcFs22AFsckF2tsDtFqEtF1posFdFgiFseFtmelpsEfhkF2anmelpFlF1ikrotcnEqEq2FfqmcDsrFor22FgFrcgDscFs22FgEeFtE2sfFs22sCoEsaF2tsD1FpeE2eFsssEciFsFnmelpFcFhkF2tcnEqEpFgkrotcnEqrEtFermcDsrE222FgBmcmr22DaEfnaF222sD1FpeForeF2tssEfiFpEoeFssD1iFstEqFppDgFstcnEqEpFg11FscnEqrAoAF2ClAEsDmDtCtBaDlAFbAEpAAAAAD2FgBiBqoBbnBaBoAAAAAAAEgDqAdBqAFrBaBoACdAAf1AACgAAAeBbCamDgEifAE2AABa1C1BgFdiAAACoCeE1ADiEifDaAEqAAFe1AcFbcAAAAAF1iFaAAACpACmFmAAAAAAAACrDaAAADG0');
        }
        $size = count(self::$QI_KB);
        $d = 0;
        $pc = 7;
        $jd += Solar::$J2000;
        $f1 = self::$QI_KB[0] - $pc;
        $f2 = self::$QI_KB[$size - 1] - $pc;
        $f3 = 2436935;
        if ($jd < $f1 || $jd >= $f3) {
            $d = floor(self::qiHigh(floor(($jd + $pc - 2451259) / 365.2422 * 24) * M_PI / 12) + 0.5);
        } else if ($jd >= $f1 && $jd < $f2) {
            for ($i = 0; $i < $size; $i += 2) {
                if ($jd + $pc < self::$QI_KB[$i + 2]) {
                    break;
                }
            }
            $d = self::$QI_KB[$i] + self::$QI_KB[$i + 1] * floor(($jd + $pc - self::$QI_KB[$i]) / self::$QI_KB[$i + 1]);
            $d = floor($d + 0.5);
            if ($d == 1683460) {
                $d++;
            }
            $d -= Solar::$J2000;
        } else if ($jd >= $f2 && $jd < $f3) {
            $d = floor(self::qiLow(floor(($jd + $pc - 2451259) / 365.2422 * 24) * M_PI / 12) + 0.5);
            $from = (int)(($jd - $f2) / 365.2422 * 24);
            $n = substr(self::$QB, $from, 1);
            if (strcmp('1', $n) == 0) {
                $d += 1;
            } elseif (strcmp('2', $n) == 0) {
                $d -= 1;
            }
        }
        return $d;
    }
    public static function qiAccurate($w)
    {
        $t = self::saLonT($w) * 36525;
        return $t - self::dtT($t) + self::$ONE_THIRD;
    }
    public static function qiAccurate2($jd)
    {
        $d = M_PI / 12;
        $w = floor(($jd + 293) / 365.2422 * 24) * $d;
        $a = self::qiAccurate($w);
        if ($a - $jd > 5) {
            return self::qiAccurate($w - $d);
        }
        if ($a - $jd < -5) {
            return self::qiAccurate($w + $d);
        }
        return $a;
    }
}
class SolarUtil
{
    public static $WEEK = array('日', '一', '二', '三', '四', '五', '六');
    public static $DAYS_OF_MONTH = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    public static $XING_ZUO = array('白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手', '摩羯', '水瓶', '双鱼');
    public static $FESTIVAL = array('1-1' => '元旦节', '2-14' => '情人节', '3-8' => '妇女节', '3-12' => '植树节', '3-15' => '消费者权益日', '4-1' => '愚人节', '5-1' => '劳动节', '5-4' => '青年节', '6-1' => '儿童节', '7-1' => '建党节', '8-1' => '建军节', '9-10' => '教师节', '10-1' => '国庆节', '10-31' => '万圣节前夜', '11-1' => '万圣节', '12-24' => '平安夜', '12-25' => '圣诞节');
    public static $WEEK_FESTIVAL = array('3-0-1' => '全国中小学生安全教育日', '5-2-0' => '母亲节', '6-3-0' => '父亲节', '11-4-4' => '感恩节');
    public static $OTHER_FESTIVAL = array('1-8' => array('周恩来逝世纪念日'), '1-10' => array('中国人民警察节'), '1-14' => array('日记情人节'), '1-21' => array('列宁逝世纪念日'), '1-26' => array('国际海关日'), '1-27' => array('国际大屠杀纪念日'), '2-2' => array('世界湿地日'), '2-4' => array('世界抗癌日'), '2-7' => array('京汉铁路罢工纪念日'), '2-10' => array('国际气象节'), '2-19' => array('邓小平逝世纪念日'), '2-20' => array('世界社会公正日'), '2-21' => array('国际母语日'), '2-24' => array('第三世界青年日'), '3-1' => array('国际海豹日'), '3-3' => array('世界野生动植物日', '全国爱耳日'), '3-5' => array('周恩来诞辰纪念日', '中国青年志愿者服务日'), '3-6' => array('世界青光眼日'), '3-7' => array('女生节'), '3-12' => array('孙中山逝世纪念日'), '3-14' => array('马克思逝世纪念日', '白色情人节'), '3-17' => array('国际航海日'), '3-18' => array('全国科技人才活动日', '全国爱肝日'), '3-20' => array('国际幸福日'), '3-21' => array('世界森林日', '世界睡眠日', '国际消除种族歧视日'), '3-22' => array('世界水日'), '3-23' => array('世界气象日'), '3-24' => array('世界防治结核病日'), '3-29' => array('中国黄花岗七十二烈士殉难纪念日'), '4-2' => array('国际儿童图书日', '世界自闭症日'), '4-4' => array('国际地雷行动日'), '4-7' => array('世界卫生日'), '4-8' => array('国际珍稀动物保护日'), '4-12' => array('世界航天日'), '4-14' => array('黑色情人节'), '4-15' => array('全民国家安全教育日'), '4-22' => array('世界地球日', '列宁诞辰纪念日'), '4-23' => array('世界读书日'), '4-24' => array('中国航天日'), '4-25' => array('儿童预防接种宣传日'), '4-26' => array('世界知识产权日', '全国疟疾日'), '4-28' => array('世界安全生产与健康日'), '4-30' => array('全国交通安全反思日'), '5-2' => array('世界金枪鱼日'), '5-3' => array('世界新闻自由日'), '5-5' => array('马克思诞辰纪念日'), '5-8' => array('世界红十字日'), '5-11' => array('世界肥胖日'), '5-12' => array('全国防灾减灾日', '护士节'), '5-14' => array('玫瑰情人节'), '5-15' => array('国际家庭日'), '5-19' => array('中国旅游日'), '5-20' => array('网络情人节'), '5-22' => array('国际生物多样性日'), '5-25' => array('525心理健康节'), '5-27' => array('上海解放日'), '5-29' => array('国际维和人员日'), '5-30' => array('中国五卅运动纪念日'), '5-31' => array('世界无烟日'), '6-3' => array('世界自行车日'), '6-5' => array('世界环境日'), '6-6' => array('全国爱眼日'), '6-8' => array('世界海洋日'), '6-11' => array('中国人口日'), '6-14' => array('世界献血日', '亲亲情人节'), '6-17' => array('世界防治荒漠化与干旱日'), '6-20' => array('世界难民日'), '6-21' => array('国际瑜伽日'), '6-25' => array('全国土地日'), '6-26' => array('国际禁毒日', '联合国宪章日'), '7-1' => array('香港回归纪念日'), '7-6' => array('国际接吻日', '朱德逝世纪念日'), '7-7' => array('七七事变纪念日'), '7-11' => array('世界人口日', '中国航海日'), '7-14' => array('银色情人节'), '7-18' => array('曼德拉国际日'), '7-30' => array('国际友谊日'), '8-3' => array('男人节'), '8-5' => array('恩格斯逝世纪念日'), '8-6' => array('国际电影节'), '8-8' => array('全民健身日'), '8-9' => array('国际土著人日'), '8-12' => array('国际青年节'), '8-14' => array('绿色情人节'), '8-19' => array('世界人道主义日', '中国医师节'), '8-22' => array('邓小平诞辰纪念日'), '8-29' => array('全国测绘法宣传日'), '9-3' => array('中国抗日战争胜利纪念日'), '9-5' => array('中华慈善日'), '9-8' => array('世界扫盲日'), '9-9' => array('毛泽东逝世纪念日', '全国拒绝酒驾日'), '9-14' => array('世界清洁地球日', '相片情人节'), '9-15' => array('国际民主日'), '9-16' => array('国际臭氧层保护日'), '9-17' => array('世界骑行日'), '9-18' => array('九一八事变纪念日'), '9-20' => array('全国爱牙日'), '9-21' => array('国际和平日'), '9-27' => array('世界旅游日'), '9-30' => array('中国烈士纪念日'), '10-1' => array('国际老年人日'), '10-2' => array('国际非暴力日'), '10-4' => array('世界动物日'), '10-11' => array('国际女童日'), '10-10' => array('辛亥革命纪念日'), '10-13' => array('国际减轻自然灾害日', '中国少年先锋队诞辰日'), '10-14' => array('葡萄酒情人节'), '10-16' => array('世界粮食日'), '10-17' => array('全国扶贫日'), '10-20' => array('世界统计日'), '10-24' => array('世界发展信息日', '程序员节'), '10-25' => array('抗美援朝纪念日'), '11-5' => array('世界海啸日'), '11-8' => array('记者节'), '11-9' => array('全国消防日'), '11-11' => array('光棍节'), '11-12' => array('孙中山诞辰纪念日'), '11-14' => array('电影情人节'), '11-16' => array('国际宽容日'), '11-17' => array('国际大学生节'), '11-19' => array('世界厕所日'), '11-28' => array('恩格斯诞辰纪念日'), '11-29' => array('国际声援巴勒斯坦人民日'), '12-1' => array('世界艾滋病日'), '12-2' => array('全国交通安全日'), '12-3' => array('世界残疾人日'), '12-4' => array('全国法制宣传日'), '12-5' => array('世界弱能人士日', '国际志愿人员日'), '12-7' => array('国际民航日'), '12-9' => array('世界足球日', '国际反腐败日'), '12-10' => array('世界人权日'), '12-11' => array('国际山岳日'), '12-12' => array('西安事变纪念日'), '12-13' => array('国家公祭日'), '12-14' => array('拥抱情人节'), '12-18' => array('国际移徙者日'), '12-26' => array('毛泽东诞辰纪念日'));
    public static function isLeapYear($year)
    {
        if ($year < 1600) {
            return $year % 4 === 0;
        }
        return ($year % 4 === 0 && $year % 100 != 0) || ($year % 400 === 0);
    }
    public static function getDaysOfMonth($year, $month)
    {
        if (1582 === $year && 10 === $month) {
            return 21;
        }
        $d = self::$DAYS_OF_MONTH[$month - 1];
        if ($month === 2 && self::isLeapYear($year)) {
            $d++;
        }
        return $d;
    }
    public static function getDaysOfYear($year)
    {
        if (1582 == $year) {
            return 355;
        }
        return self::isLeapYear($year) ? 366 : 365;
    }
    public static function getDaysInYear($year, $month, $day)
    {
        $days = 0;
        for ($i = 1; $i < $month; $i++) {
            $days += self::getDaysOfMonth($year, $i);
        }
        $d = $day;
        if (1582 === $year && 10 === $month) {
            if ($day >= 15) {
                $d -= 10;
            } else if ($day > 4) {
                throw new RuntimeException(sprintf('wrong solar year %d month %d day %d', $year, $month, $day));
            }
        }
        $days += $d;
        return $days;
    }
    public static function getWeeksOfMonth($year, $month, $start)
    {
        return (int)ceil((self::getDaysOfMonth($year, $month) + Solar::fromYmd($year, $month, 1)->getWeek() - $start) / count(self::$WEEK));
    }
    public static function getDaysBetween($ay, $am, $ad, $by, $bm, $bd)
    {
        if ($ay == $by) {
            $n = self::getDaysInYear($by, $bm, $bd) - self::getDaysInYear($ay, $am, $ad);
        } else if ($ay > $by) {
            $days = self::getDaysOfYear($by) - self::getDaysInYear($by, $bm, $bd);
            for ($i = $by + 1; $i < $ay; $i++) {
                $days += self::getDaysOfYear($i);
            }
            $days += self::getDaysInYear($ay, $am, $ad);
            $n = -$days;
        } else {
            $days = self::getDaysOfYear($ay) - self::getDaysInYear($ay, $am, $ad);
            for ($i = $ay + 1; $i < $by; $i++) {
                $days += self::getDaysOfYear($i);
            }
            $days += self::getDaysInYear($by, $bm, $bd);
            $n = $days;
        }
        return $n;
    }
}

namespace com\nlf\calendar;

use com\nlf\calendar\util\LunarUtil;
use com\nlf\calendar\util\SolarUtil;
use com\nlf\calendar\util\ShouXingUtil;
use com\nlf\calendar\util\HolidayUtil;
use com\nlf\calendar\util\FotoUtil;
use com\nlf\calendar\util\TaoUtil;
use DateTime;
use DateTimeZone;
use RuntimeException;

class DaYun
{
    private $startYear;
    private $endYear;
    private $startAge;
    private $endAge;
    private $index;
    private $yun;
    private $lunar;
    public function __construct($yun, $index)
    {
        $this->yun = $yun;
        $this->lunar = $yun->getLunar();
        $this->index = $index;
        $birthYear = $this->lunar->getSolar()->getYear();
        $year = $yun->getStartSolar()->getYear();
        if ($index < 1) {
            $this->startYear = $birthYear;
            $this->startAge = 1;
            $this->endYear = $year - 1;
            $this->endAge = $year - $birthYear;
        } else {
            $add = ($index - 1) * 10;
            $this->startYear = $year + $add;
            $this->startAge = $this->startYear - $birthYear + 1;
            $this->endYear = $this->startYear + 9;
            $this->endAge = $this->startAge + 9;
        }
    }
    public function getStartYear()
    {
        return $this->startYear;
    }
    public function getEndYear()
    {
        return $this->endYear;
    }
    public function getStartAge()
    {
        return $this->startAge;
    }
    public function getEndAge()
    {
        return $this->endAge;
    }
    public function getIndex()
    {
        return $this->index;
    }
    public function getYun()
    {
        return $this->yun;
    }
    public function getLunar()
    {
        return $this->lunar;
    }
    public function getGanZhi()
    {
        if ($this->index < 1) {
            return '';
        }
        $offset = LunarUtil::getJiaZiIndex($this->lunar->getMonthInGanZhiExact());
        $offset += $this->yun->isForward() ? $this->index : -$this->index;
        $size = count(LunarUtil::$JIA_ZI);
        if ($offset >= $size) {
            $offset -= $size;
        }
        if ($offset < 0) {
            $offset += $size;
        }
        return LunarUtil::$JIA_ZI[$offset];
    }
    public function getXun()
    {
        return LunarUtil::getXun($this->getGanZhi());
    }
    public function getXunKong()
    {
        return LunarUtil::getXunKong($this->getGanZhi());
    }
    public function getLiuNian()
    {
        return $this->getLiuNianBy(10);
    }
    public function getLiuNianBy($n)
    {
        if ($this->index < 1) {
            $n = $this->endYear - $this->startYear + 1;
        }
        $l = array();
        for ($i = 0; $i < $n; $i++) {
            $l[] = new LiuNian($this, $i);
        }
        return $l;
    }
    public function getXiaoYun()
    {
        return $this->getXiaoYunBy(10);
    }
    public function getXiaoYunBy($n)
    {
        if ($this->index < 1) {
            $n = $this->endYear - $this->startYear + 1;
        }
        $l = array();
        for ($i = 0; $i < $n; $i++) {
            $l[] = new XiaoYun($this, $i, $this->yun->isForward());
        }
        return $l;
    }
}
class EightChar
{
    private $sect = 2;
    private $lunar;
    private static $CHANG_SHENG_OFFSET = array('甲' => 1, '丙' => 10, '戊' => 10, '庚' => 7, '壬' => 4, '乙' => 6, '丁' => 9, '己' => 9, '辛' => 0, '癸' => 3);
    public static $MONTH_ZHI = array('', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥', '子', '丑');
    public static $CHANG_SHENG = array('长生', '沐浴', '冠带', '临官', '帝旺', '衰', '病', '死', '墓', '绝', '胎', '养');
    function __construct($lunar)
    {
        $this->lunar = $lunar;
    }
    public static function fromLunar($lunar)
    {
        return new EightChar($lunar);
    }
    public function toString()
    {
        return $this->getYear() . ' ' . $this->getMonth() . ' ' . $this->getDay() . ' ' . $this->getTime();
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function getSect()
    {
        return $this->sect;
    }
    public function setSect($sect)
    {
        $this->sect = (1 == $sect) ? 1 : 2;
    }
    public function getLunar()
    {
        return $this->lunar;
    }
    public function getYear()
    {
        return $this->lunar->getYearInGanZhiExact();
    }
    public function getYearGan()
    {
        return $this->lunar->getYearGanExact();
    }
    public function getYearZhi()
    {
        return $this->lunar->getYearZhiExact();
    }
    public function getYearHideGan()
    {
        return LunarUtil::$ZHI_HIDE_GAN[$this->getYearZhi()];
    }
    public function getYearWuXing()
    {
        return LunarUtil::$WU_XING_GAN[$this->getYearGan()] . LunarUtil::$WU_XING_ZHI[$this->getYearZhi()];
    }
    public function getYearNaYin()
    {
        return LunarUtil::$NAYIN[$this->getYear()];
    }
    public function getYearShiShenGan()
    {
        return LunarUtil::$SHI_SHEN[$this->getDayGan() . $this->getYearGan()];
    }
    private function getShiShenZhi($zhi)
    {
        $hideGan = LunarUtil::$ZHI_HIDE_GAN[$zhi];
        $l = array();
        foreach ($hideGan as $gan) {
            $l[] = LunarUtil::$SHI_SHEN[$this->getDayGan() . $gan];
        }
        return $l;
    }
    public function getYearShiShenZhi()
    {
        return $this->getShiShenZhi($this->getYearZhi());
    }
    private function getDiShi($zhiIndex)
    {
        $index = self::$CHANG_SHENG_OFFSET[$this->getDayGan()] + ($this->getDayGanIndex() % 2 == 0 ? $zhiIndex : 0 - $zhiIndex);
        if ($index >= 12) {
            $index -= 12;
        }
        if ($index < 0) {
            $index += 12;
        }
        return self::$CHANG_SHENG[$index];
    }
    public function getYearDiShi()
    {
        return $this->getDiShi($this->lunar->getYearZhiIndexExact());
    }
    public function getMonth()
    {
        return $this->lunar->getMonthInGanZhiExact();
    }
    public function getMonthGan()
    {
        return $this->lunar->getMonthGanExact();
    }
    public function getMonthZhi()
    {
        return $this->lunar->getMonthZhiExact();
    }
    public function getMonthHideGan()
    {
        return LunarUtil::$ZHI_HIDE_GAN[$this->getMonthZhi()];
    }
    public function getMonthWuXing()
    {
        return LunarUtil::$WU_XING_GAN[$this->getMonthGan()] . LunarUtil::$WU_XING_ZHI[$this->getMonthZhi()];
    }
    public function getMonthNaYin()
    {
        return LunarUtil::$NAYIN[$this->getMonth()];
    }
    public function getMonthShiShenGan()
    {
        return LunarUtil::$SHI_SHEN[$this->getDayGan() . $this->getMonthGan()];
    }
    public function getMonthShiShenZhi()
    {
        return $this->getShiShenZhi($this->getMonthZhi());
    }
    public function getMonthDiShi()
    {
        return $this->getDiShi($this->lunar->getMonthZhiIndexExact());
    }
    public function getDay()
    {
        return (2 == $this->sect) ? $this->lunar->getDayInGanZhiExact2() : $this->lunar->getDayInGanZhiExact();
    }
    public function getDayGan()
    {
        return (2 == $this->sect) ? $this->lunar->getDayGanExact2() : $this->lunar->getDayGanExact();
    }
    public function getDayZhi()
    {
        return (2 == $this->sect) ? $this->lunar->getDayZhiExact2() : $this->lunar->getDayZhiExact();
    }
    public function getDayHideGan()
    {
        return LunarUtil::$ZHI_HIDE_GAN[$this->getDayZhi()];
    }
    public function getDayWuXing()
    {
        return LunarUtil::$WU_XING_GAN[$this->getDayGan()] . LunarUtil::$WU_XING_ZHI[$this->getDayZhi()];
    }
    public function getDayNaYin()
    {
        return LunarUtil::$NAYIN[$this->getDay()];
    }
    public function getDayShiShenGan()
    {
        return '日主';
    }
    public function getDayShiShenZhi()
    {
        return $this->getShiShenZhi($this->getDayZhi());
    }
    public function getDayGanIndex()
    {
        return (2 == $this->sect) ? $this->lunar->getDayGanIndexExact2() : $this->lunar->getDayGanIndexExact();
    }
    public function getDayZhiIndex()
    {
        return (2 == $this->sect) ? $this->lunar->getDayZhiIndexExact2() : $this->lunar->getDayZhiIndexExact();
    }
    public function getDayDiShi()
    {
        return $this->getDiShi($this->getDayZhiIndex());
    }
    public function getTime()
    {
        return $this->lunar->getTimeInGanZhi();
    }
    public function getTimeGan()
    {
        return $this->lunar->getTimeGan();
    }
    public function getTimeZhi()
    {
        return $this->lunar->getTimeZhi();
    }
    public function getTimeHideGan()
    {
        return LunarUtil::$ZHI_HIDE_GAN[$this->getTimeZhi()];
    }
    public function getTimeWuXing()
    {
        return LunarUtil::$WU_XING_GAN[$this->lunar->getTimeGan()] . LunarUtil::$WU_XING_ZHI[$this->lunar->getTimeZhi()];
    }
    public function getTimeNaYin()
    {
        return LunarUtil::$NAYIN[$this->getTime()];
    }
    public function getTimeShiShenGan()
    {
        return LunarUtil::$SHI_SHEN[$this->getDayGan() . $this->getTimeGan()];
    }
    public function getTimeShiShenZhi()
    {
        return $this->getShiShenZhi($this->getTimeZhi());
    }
    public function getTimeDiShi()
    {
        return $this->getDiShi($this->lunar->getTimeZhiIndex());
    }
    public function getTaiYuan()
    {
        $ganIndex = $this->lunar->getMonthGanIndexExact() + 1;
        if ($ganIndex >= 10) {
            $ganIndex -= 10;
        }
        $zhiIndex = $this->lunar->getMonthZhiIndexExact() + 3;
        if ($zhiIndex >= 12) {
            $zhiIndex -= 12;
        }
        return LunarUtil::$GAN[$ganIndex + 1] . LunarUtil::$ZHI[$zhiIndex + 1];
    }
    public function getTaiYuanNaYin()
    {
        return LunarUtil::$NAYIN[$this->getTaiYuan()];
    }
    public function getTaiXi()
    {
        $ganIndex = (2 == $this->sect) ? $this->lunar->getDayGanIndexExact2() : $this->lunar->getDayGanIndexExact();
        $zhiIndex = (2 == $this->sect) ? $this->lunar->getDayZhiIndexExact2() : $this->lunar->getDayZhiIndexExact();
        return LunarUtil::$HE_GAN_5[$ganIndex] . LunarUtil::$HE_ZHI_6[$zhiIndex];
    }
    public function getTaiXiNaYin()
    {
        return LunarUtil::$NAYIN[$this->getTaiXi()];
    }
    public function getMingGong()
    {
        $offset = LunarUtil::index($this->getMonthZhi(), self::$MONTH_ZHI, 0) + LunarUtil::index($this->getTimeZhi(), self::$MONTH_ZHI, 0);
        $offset = ($offset >= 14 ? 26 : 14) - $offset;
        $ganIndex = ($this->lunar->getYearGanIndexExact() + 1) * 2 + $offset;
        while ($ganIndex > 10) {
            $ganIndex -= 10;
        }
        return sprintf('%s%s', LunarUtil::$GAN[$ganIndex], self::$MONTH_ZHI[$offset]);
    }
    public function getMingGongNaYin()
    {
        return LunarUtil::$NAYIN[$this->getMingGong()];
    }
    public function getShenGong()
    {
        $offset = (LunarUtil::index($this->getMonthZhi(), self::$MONTH_ZHI, 0) + LunarUtil::index($this->getTimeZhi(), LunarUtil::$ZHI, 0) - 1) % 12;
        $ganIndex = ($this->lunar->getYearGanIndexExact() + 1) * 2 + $offset;
        while ($ganIndex > 10) {
            $ganIndex -= 10;
        }
        return sprintf('%s%s', LunarUtil::$GAN[$ganIndex + 1], self::$MONTH_ZHI[$offset + 1]);
    }
    public function getShenGongNaYin()
    {
        return LunarUtil::$NAYIN[$this->getShenGong()];
    }
    public function getYun($gender, $sect = 1)
    {
        return new Yun($this, $gender, $sect);
    }
    public function getYunBySect($gender, $sect)
    {
        return new Yun($this, $gender, $sect);
    }
    public function getYearXun()
    {
        return $this->lunar->getYearXunExact();
    }
    public function getYearXunKong()
    {
        return $this->lunar->getYearXunKongExact();
    }
    public function getMonthXun()
    {
        return $this->lunar->getMonthXunExact();
    }
    public function getMonthXunKong()
    {
        return $this->lunar->getMonthXunKongExact();
    }
    public function getDayXun()
    {
        return (2 == $this->sect) ? $this->lunar->getDayXunExact2() : $this->lunar->getDayXunExact();
    }
    public function getDayXunKong()
    {
        return (2 == $this->sect) ? $this->lunar->getDayXunKongExact2() : $this->lunar->getDayXunKongExact();
    }
    public function getTimeXun()
    {
        return $this->lunar->getTimeXun();
    }
    public function getTimeXunKong()
    {
        return $this->lunar->getTimeXunKong();
    }
}
class Fu
{
    private $name;
    private $index;
    function __construct($name, $index)
    {
        $this->name = $name;
        $this->index = $index;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
    }
    public function getIndex()
    {
        return $this->index;
    }
    public function setIndex($index)
    {
        $this->index = $index;
    }
    public function toString()
    {
        return $this->name;
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        return $this->name . '第' . $this->index . '天';
    }
}
class Holiday
{
    private $day;
    private $name;
    private $work = false;
    private $target;
    function __construct($day, $name, $work, $target)
    {
        if (strpos($day, '-') !== false) {
            $this->day = $day;
        } else {
            $this->day = substr($day, 0, 4) . '-' . substr($day, 4, 2) . '-' . substr($day, 6);
        }
        $this->name = $name;
        $this->work = $work;
        if (strpos($day, '-') !== false) {
            $this->target = $target;
        } else {
            $this->target = substr($target, 0, 4) . '-' . substr($target, 4, 2) . '-' . substr($target, 6);
        }
    }
    public function setDay($day)
    {
        $this->day = $day;
    }
    public function setName($name)
    {
        $this->name = $name;
    }
    public function setWork($work)
    {
        $this->work = $work;
    }
    public function setTarget($target)
    {
        $this->target = $target;
    }
    public function getDay()
    {
        return $this->day;
    }
    public function getName()
    {
        return $this->name;
    }
    public function isWork()
    {
        return $this->work;
    }
    public function getTarget()
    {
        return $this->target;
    }
    public function toString()
    {
        return $this->day . ' ' . $this->name . ($this->work ? '调休' : '') . ' ' . $this->target;
    }
    public function __toString()
    {
        return $this->toString();
    }
}
class JieQi
{
    private $name;
    private $solar;
    private $jie;
    private $qi;
    function __construct($name, $solar)
    {
        $this->setName($name);
        $this->solar = $solar;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
        for ($i = 0, $j = count(Lunar::$JIE_QI); $i < $j; $i++) {
            if (strcmp($name, Lunar::$JIE_QI[$i]) == 0) {
                if ($i % 2 == 0) {
                    $this->qi = true;
                } else {
                    $this->jie = true;
                }
                return;
            }
        }
    }
    public function getSolar()
    {
        return $this->solar;
    }
    public function setSolar($solar)
    {
        $this->solar = $solar;
    }
    public function isJie()
    {
        return $this->jie;
    }
    public function isQi()
    {
        return $this->qi;
    }
    public function toString()
    {
        return $this->name;
    }
    public function __toString()
    {
        return $this->toString();
    }
}
class LiuNian
{
    private $index;
    private $daYun;
    private $year;
    private $age;
    private $lunar;
    public function __construct(DaYun $daYun, $index)
    {
        $this->daYun = $daYun;
        $this->lunar = $daYun->getLunar();
        $this->index = $index;
        $this->year = $daYun->getStartYear() + $index;
        $this->age = $daYun->getStartAge() + $index;
    }
    public function getIndex()
    {
        return $this->index;
    }
    public function getDaYun()
    {
        return $this->daYun;
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getAge()
    {
        return $this->age;
    }
    public function getLunar()
    {
        return $this->lunar;
    }
    public function getGanZhi()
    {
        $jieQi = $this->lunar->getJieQiTable();
        $offset = LunarUtil::getJiaZiIndex($jieQi['立春']->getLunar()->getYearInGanZhiExact()) + $this->index;
        if ($this->daYun->getIndex() > 0) {
            $offset += $this->daYun->getStartAge() - 1;
        }
        $offset %= count(LunarUtil::$JIA_ZI);
        return LunarUtil::$JIA_ZI[$offset];
    }
    public function getXun()
    {
        return LunarUtil::getXun($this->getGanZhi());
    }
    public function getXunKong()
    {
        return LunarUtil::getXunKong($this->getGanZhi());
    }
    public function getLiuYue()
    {
        $n = 12;
        $l = array();
        for ($i = 0; $i < $n; $i++) {
            $l[] = new LiuYue($this, $i);
        }
        return $l;
    }
}
class LiuYue
{
    private $index;
    private $liuNian;
    public function __construct(LiuNian $liuNian, $index)
    {
        $this->liuNian = $liuNian;
        $this->index = $index;
    }
    public function getIndex()
    {
        return $this->index;
    }
    public function getLiuNian()
    {
        return $this->liuNian;
    }
    public function getMonthInChinese()
    {
        return LunarUtil::$MONTH[$this->index + 1];
    }
    public function getGanZhi()
    {
        $offset = 0;
        $liuNianGanZhi = $this->liuNian->getGanZhi();
        $yearGan = substr($liuNianGanZhi, 0, strlen($liuNianGanZhi) / 2);
        if ('甲' == $yearGan || '己' == $yearGan) {
            $offset = 2;
        } else if ('乙' == $yearGan || '庚' == $yearGan) {
            $offset = 4;
        } else if ('丙' == $yearGan || '辛' == $yearGan) {
            $offset = 6;
        } else if ('丁' == $yearGan || '壬' == $yearGan) {
            $offset = 8;
        }
        $gan = LunarUtil::$GAN[($this->index + $offset) % 10 + 1];
        $zhi = LunarUtil::$ZHI[($this->index + LunarUtil::$BASE_MONTH_ZHI_INDEX) % 12 + 1];
        return $gan . $zhi;
    }
    public function getXun()
    {
        return LunarUtil::getXun($this->getGanZhi());
    }
    public function getXunKong()
    {
        return LunarUtil::getXunKong($this->getGanZhi());
    }
}
class LunarTime
{
    private $ganIndex;
    private $zhiIndex;
    private $lunar;
    private function __construct($lunarYear, $lunarMonth, $lunarDay, $hour, $minute, $second)
    {
        $this->lunar = Lunar::fromYmdHms($lunarYear, $lunarMonth, $lunarDay, $hour, $minute, $second);
        $this->zhiIndex = LunarUtil::getTimeZhiIndex(sprintf('%02d:%02d', $hour, $minute));
        $this->ganIndex = ($this->lunar->getDayGanIndexExact() % 5 * 2 + $this->zhiIndex) % 10;
    }
    public static function fromYmdHms($lunarYear, $lunarMonth, $lunarDay, $hour, $minute, $second)
    {
        return new LunarTime($lunarYear, $lunarMonth, $lunarDay, $hour, $minute, $second);
    }
    public function getShengXiao()
    {
        return LunarUtil::$SHENG_XIAO[$this->zhiIndex + 1];
    }
    public function getZhi()
    {
        return LunarUtil::$ZHI[$this->zhiIndex + 1];
    }
    public function getGan()
    {
        return LunarUtil::$GAN[$this->ganIndex + 1];
    }
    public function getGanZhi()
    {
        return $this->getGan() . $this->getZhi();
    }
    public function getPositionXi()
    {
        return LunarUtil::$POSITION_XI[$this->ganIndex + 1];
    }
    public function getPositionXiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionXi()];
    }
    public function getPositionYangGui()
    {
        return LunarUtil::$POSITION_YANG_GUI[$this->ganIndex + 1];
    }
    public function getPositionYangGuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionYangGui()];
    }
    public function getPositionYinGui()
    {
        return LunarUtil::$POSITION_YIN_GUI[$this->ganIndex + 1];
    }
    public function getPositionYinGuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionYinGui()];
    }
    public function getPositionFu($sect = 2)
    {
        $fu = 1 == $sect ? LunarUtil::$POSITION_FU : LunarUtil::$POSITION_FU_2;
        return $fu[$this->ganIndex + 1];
    }
    public function getPositionFuBySect($sect)
    {
        $fu = 1 == $sect ? LunarUtil::$POSITION_FU : LunarUtil::$POSITION_FU_2;
        return $fu[$this->ganIndex + 1];
    }
    public function getPositionFuDesc($sect = 2)
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionFu($sect)];
    }
    public function getPositionFuDescBySect($sect)
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionFu($sect)];
    }
    public function getPositionCai()
    {
        return LunarUtil::$POSITION_CAI[$this->ganIndex + 1];
    }
    public function getPositionCaiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionCai()];
    }
    public function getChong()
    {
        return LunarUtil::$CHONG[$this->zhiIndex];
    }
    public function getChongGan()
    {
        return LunarUtil::$CHONG_GAN[$this->ganIndex];
    }
    public function getChongGanTie()
    {
        return LunarUtil::$CHONG_GAN_TIE[$this->ganIndex];
    }
    public function getChongShengXiao()
    {
        $chong = $this->getChong();
        for ($i = 0, $j = count(LunarUtil::$ZHI); $i < $j; $i++) {
            if (strcmp(LunarUtil::$ZHI[$i], $chong) === 0) {
                return LunarUtil::$SHENG_XIAO[$i];
            }
        }
        return '';
    }
    public function getChongDesc()
    {
        return '(' . $this->getChongGan() . $this->getChong() . ')' . $this->getChongShengXiao();
    }
    public function getSha()
    {
        return LunarUtil::$SHA[$this->getZhi()];
    }
    public function getNaYin()
    {
        return LunarUtil::$NAYIN[$this->getGanZhi()];
    }
    public function getTianShen()
    {
        return LunarUtil::$TIAN_SHEN[($this->zhiIndex + LunarUtil::$ZHI_TIAN_SHEN_OFFSET[$this->lunar->getDayZhiExact()]) % 12 + 1];
    }
    public function getTianShenType()
    {
        return LunarUtil::$TIAN_SHEN_TYPE[$this->getTianShen()];
    }
    public function getTianShenLuck()
    {
        return LunarUtil::$TIAN_SHEN_TYPE_LUCK[$this->getTianShenType()];
    }
    public function getYi()
    {
        return LunarUtil::getTimeYi($this->lunar->getDayInGanZhiExact(), $this->getGanZhi());
    }
    public function getJi()
    {
        return LunarUtil::getTimeJi($this->lunar->getDayInGanZhiExact(), $this->getGanZhi());
    }
    public function getNineStar()
    {
        $solarYmd = $this->lunar->getSolar()->toYmd();
        $jieQi = $this->lunar->getJieQiTable();
        $asc = false;
        if (strcmp($solarYmd, $jieQi['冬至']->toYmd()) >= 0 && strcmp($solarYmd, $jieQi['夏至']->toYmd()) < 0) {
            $asc = true;
        }
        $start = $asc ? 7 : 3;
        $dayZhi = $this->lunar->getDayZhi();
        if (strpos('子午卯酉', $dayZhi) !== false) {
            $start = $asc ? 1 : 9;
        } else if (strpos('辰戌丑未', $dayZhi) !== false) {
            $start = $asc ? 4 : 6;
        }
        $index = $asc ? $start + $this->zhiIndex - 1 : $start - $this->zhiIndex - 1;
        if ($index > 8) {
            $index -= 9;
        }
        if ($index < 0) {
            $index += 9;
        }
        return new NineStar($index);
    }
    public function getGanIndex()
    {
        return $this->ganIndex;
    }
    public function getZhiIndex()
    {
        return $this->zhiIndex;
    }
    public function toString()
    {
        return $this->getGanZhi();
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function getXun()
    {
        return LunarUtil::getXun($this->getGanZhi());
    }
    public function getXunKong()
    {
        return LunarUtil::getXunKong($this->getGanZhi());
    }
    public function getMinHm()
    {
        $hour = $this->lunar->getHour();
        if ($hour < 1) {
            return '00:00';
        } else if ($hour > 22) {
            return '23:00';
        }
        return sprintf('%02d:00', $hour % 2 == 0 ? $hour - 1 : $hour);
    }
    public function getMaxHm()
    {
        $hour = $this->lunar->getHour();
        if ($hour < 1) {
            return '00:59';
        } else if ($hour > 22) {
            return '23:59';
        }
        return sprintf('%02d:59', $hour % 2 == 0 ? $hour : $hour + 1);
    }
}
class Lunar
{
    public static $JIE_QI = array('冬至', '小寒', '大寒', '立春', '雨水', '惊蛰', '春分', '清明', '谷雨', '立夏', '小满', '芒种', '夏至', '小暑', '大暑', '立秋', '处暑', '白露', '秋分', '寒露', '霜降', '立冬', '小雪', '大雪');
    public static $JIE_QI_IN_USE = array('DA_XUE', '冬至', '小寒', '大寒', '立春', '雨水', '惊蛰', '春分', '清明', '谷雨', '立夏', '小满', '芒种', '夏至', '小暑', '大暑', '立秋', '处暑', '白露', '秋分', '寒露', '霜降', '立冬', '小雪', '大雪', 'DONG_ZHI', 'XIAO_HAN', 'DA_HAN', 'LI_CHUN', 'YU_SHUI', 'JING_ZHE');
    private $year;
    private $month;
    private $day;
    private $hour;
    private $minute;
    private $second;
    private $solar;
    private $timeGanIndex;
    private $timeZhiIndex;
    private $dayGanIndex;
    private $dayGanIndexExact;
    private $dayGanIndexExact2;
    private $dayZhiIndex;
    private $dayZhiIndexExact;
    private $dayZhiIndexExact2;
    private $monthGanIndex;
    private $monthZhiIndex;
    private $monthGanIndexExact;
    private $monthZhiIndexExact;
    private $yearGanIndex;
    private $yearZhiIndex;
    private $yearGanIndexByLiChun;
    private $yearZhiIndexByLiChun;
    private $yearGanIndexExact;
    private $yearZhiIndexExact;
    private $weekIndex;
    private $jieQi = array();
    private $eightChar = null;
    private function __construct($lunarYear, $lunarMonth, $lunarDay, $hour, $minute, $second, $solar, $y)
    {
        $this->year = intval($lunarYear);
        $this->month = intval($lunarMonth);
        $this->day = intval($lunarDay);
        $this->hour = intval($hour);
        $this->minute = intval($minute);
        $this->second = intval($second);
        $this->solar = $solar;
        $this->compute($y);
    }
    public static function fromYmd($lunarYear, $lunarMonth, $lunarDay)
    {
        return self::fromYmdHms($lunarYear, $lunarMonth, $lunarDay, 0, 0, 0);
    }
    public static function fromYmdHms($lunarYear, $lunarMonth, $lunarDay, $hour, $minute, $second)
    {
        $y = LunarYear::fromYear($lunarYear);
        $m = $y->getMonth($lunarMonth);
        $noon = Solar::fromJulianDay($m->getFirstJulianDay() + $lunarDay - 1);
        $solar = Solar::fromYmdHms($noon->getYear(), $noon->getMonth(), $noon->getDay(), $hour, $minute, $second);
        if ($noon->getYear() != $lunarYear) {
            $y = LunarYear::fromYear($noon->getYear());
        }
        return new Lunar($lunarYear, $lunarMonth, $lunarDay, $hour, $minute, $second, $solar, $y);
    }
    public static function fromDate($date)
    {
        return self::fromSolar(Solar::fromDate($date));
    }
    public static function fromSolar($solar)
    {
        $ly = LunarYear::fromYear($solar->getYear());
        $lunarYear = 0;
        $lunarMonth = 0;
        $lunarDay = 0;
        foreach ($ly->getMonths() as $m) {
            $days = $solar->subtract(Solar::fromJulianDay($m->getFirstJulianDay()));
            if ($days < $m->getDayCount()) {
                $lunarYear = $m->getYear();
                $lunarMonth = $m->getMonth();
                $lunarDay = $days + 1;
                break;
            }
        }
        return new Lunar($lunarYear, $lunarMonth, $lunarDay, $solar->getHour(), $solar->getMinute(), $solar->getSecond(), $solar, $ly);
    }
    private function computeJieQi($y)
    {
        $jds = $y->getJieQiJulianDays();
        for ($i = 0, $j = count(self::$JIE_QI_IN_USE); $i < $j; $i++) {
            $this->jieQi[self::$JIE_QI_IN_USE[$i]] = Solar::fromJulianDay($jds[$i]);
        }
    }
    private function computeYear()
    {
        $offset = $this->year - 4;
        $yearGanIndex = $offset % 10;
        $yearZhiIndex = $offset % 12;
        if ($yearGanIndex < 0) {
            $yearGanIndex += 10;
        }
        if ($yearZhiIndex < 0) {
            $yearZhiIndex += 12;
        }
        $g = $yearGanIndex;
        $z = $yearZhiIndex;
        $gExact = $yearGanIndex;
        $zExact = $yearZhiIndex;
        $solarYear = $this->solar->getYear();
        $solarYmd = $this->solar->toYmd();
        $solarYmdHms = $this->solar->toYmdHms();
        $liChun = $this->jieQi['立春'];
        if ($liChun->getYear() != $solarYear) {
            $liChun = $this->jieQi['LI_CHUN'];
        }
        $liChunYmd = $liChun->toYmd();
        $liChunYmdHms = $liChun->toYmdHms();
        if ($this->year === $solarYear) {
            if (strcmp($solarYmd, $liChunYmd) < 0) {
                $g--;
                $z--;
            }
            if (strcmp($solarYmdHms, $liChunYmdHms) < 0) {
                $gExact--;
                $zExact--;
            }
        } else if ($this->year < $solarYear) {
            if (strcmp($solarYmd, $liChunYmd) >= 0) {
                $g++;
                $z++;
            }
            if (strcmp($solarYmdHms, $liChunYmdHms) >= 0) {
                $gExact++;
                $zExact++;
            }
        }
        $this->yearGanIndex = $yearGanIndex;
        $this->yearZhiIndex = $yearZhiIndex;
        $this->yearGanIndexByLiChun = ($g < 0 ? $g + 10 : $g) % 10;
        $this->yearZhiIndexByLiChun = ($z < 0 ? $z + 12 : $z) % 12;
        $this->yearGanIndexExact = ($gExact < 0 ? $gExact + 10 : $gExact) % 10;
        $this->yearZhiIndexExact = ($zExact < 0 ? $zExact + 12 : $zExact) % 12;
    }
    private function computeMonth()
    {
        $start = null;
        $ymd = $this->solar->toYmd();
        $time = $this->solar->toYmdHms();
        $size = count(self::$JIE_QI_IN_USE);
        $index = -3;
        for ($i = 0; $i < $size; $i += 2) {
            $end = $this->jieQi[self::$JIE_QI_IN_USE[$i]];
            $symd = (null == $start) ? $ymd : $start->toYmd();
            if (strcmp($ymd, $symd) >= 0 && strcmp($ymd, $end->toYmd()) < 0) {
                break;
            }
            $start = $end;
            $index++;
        }
        $offset = ((($this->yearGanIndexByLiChun + ($index < 0 ? 1 : 0)) % 5 + 1) * 2) % 10;
        $this->monthGanIndex = (($index < 0 ? $index + 10 : $index) + $offset) % 10;
        $this->monthZhiIndex = (($index < 0 ? $index + 12 : $index) + LunarUtil::$BASE_MONTH_ZHI_INDEX) % 12;
        $start = null;
        $index = -3;
        for ($i = 0; $i < $size; $i += 2) {
            $end = $this->jieQi[self::$JIE_QI_IN_USE[$i]];
            $stime = null == $start ? $time : $start->toYmdHms();
            if (strcmp($time, $stime) >= 0 && strcmp($time, $end->toYmdHms()) < 0) {
                break;
            }
            $start = $end;
            $index++;
        }
        $offset = ((($this->yearGanIndexExact + ($index < 0 ? 1 : 0)) % 5 + 1) * 2) % 10;
        $this->monthGanIndexExact = (($index < 0 ? $index + 10 : $index) + $offset) % 10;
        $this->monthZhiIndexExact = (($index < 0 ? $index + 12 : $index) + LunarUtil::$BASE_MONTH_ZHI_INDEX) % 12;
    }
    private function computeDay()
    {
        $noon = Solar::fromYmdHms($this->solar->getYear(), $this->solar->getMonth(), $this->solar->getDay(), 12, 0, 0);
        $offset = (int)$noon->getJulianDay() - 11;
        $dayGanIndex = $offset % 10;
        $dayZhiIndex = $offset % 12;
        $this->dayGanIndex = $dayGanIndex;
        $this->dayZhiIndex = $dayZhiIndex;
        $dayGanExact = $dayGanIndex;
        $dayZhiExact = $dayZhiIndex;
        $this->dayGanIndexExact2 = $dayGanExact;
        $this->dayZhiIndexExact2 = $dayZhiExact;
        $hm = ($this->hour < 10 ? '0' : '') . $this->hour . ':' . ($this->minute < 10 ? '0' : '') . $this->minute;
        if (strcmp($hm, '23:00') >= 0 && strcmp($hm, '23:59') <= 0) {
            $dayGanExact++;
            if ($dayGanExact >= 10) {
                $dayGanExact -= 10;
            }
            $dayZhiExact++;
            if ($dayZhiExact >= 12) {
                $dayZhiExact -= 12;
            }
        }
        $this->dayGanIndexExact = $dayGanExact;
        $this->dayZhiIndexExact = $dayZhiExact;
    }
    private function computeTime()
    {
        $this->timeZhiIndex = LunarUtil::getTimeZhiIndex(($this->hour < 10 ? '0' : '') . $this->hour . ':' . ($this->minute < 10 ? '0' : '') . $this->minute);
        $this->timeGanIndex = ($this->dayGanIndexExact % 5 * 2 + $this->timeZhiIndex) % 10;
    }
    private function computeWeek()
    {
        $this->weekIndex = $this->solar->getWeek();
    }
    private function compute($y)
    {
        $this->computeJieQi($y);
        $this->computeYear();
        $this->computeMonth();
        $this->computeDay();
        $this->computeTime();
        $this->computeWeek();
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getMonth()
    {
        return $this->month;
    }
    public function getDay()
    {
        return $this->day;
    }
    public function getHour()
    {
        return $this->hour;
    }
    public function getMinute()
    {
        return $this->minute;
    }
    public function getSecond()
    {
        return $this->second;
    }
    public function getSolar()
    {
        return $this->solar;
    }
    public function getYearGan()
    {
        return LunarUtil::$GAN[$this->yearGanIndex + 1];
    }
    public function getYearGanByLiChun()
    {
        return LunarUtil::$GAN[$this->yearGanIndexByLiChun + 1];
    }
    public function getYearGanExact()
    {
        return LunarUtil::$GAN[$this->yearGanIndexExact + 1];
    }
    public function getYearZhi()
    {
        return LunarUtil::$ZHI[$this->yearZhiIndex + 1];
    }
    public function getYearZhiByLiChun()
    {
        return LunarUtil::$ZHI[$this->yearZhiIndexByLiChun + 1];
    }
    public function getYearZhiExact()
    {
        return LunarUtil::$ZHI[$this->yearZhiIndexExact + 1];
    }
    public function getYearInGanZhi()
    {
        return $this->getYearGan() . $this->getYearZhi();
    }
    public function getYearInGanZhiByLiChun()
    {
        return $this->getYearGanByLiChun() . $this->getYearZhiByLiChun();
    }
    public function getYearInGanZhiExact()
    {
        return $this->getYearGanExact() . $this->getYearZhiExact();
    }
    public function getMonthInGanZhi()
    {
        return $this->getMonthGan() . $this->getMonthZhi();
    }
    public function getMonthInGanZhiExact()
    {
        return $this->getMonthGanExact() . $this->getMonthZhiExact();
    }
    public function getMonthGan()
    {
        return LunarUtil::$GAN[$this->monthGanIndex + 1];
    }
    public function getMonthGanExact()
    {
        return LunarUtil::$GAN[$this->monthGanIndexExact + 1];
    }
    public function getMonthZhi()
    {
        return LunarUtil::$ZHI[$this->monthZhiIndex + 1];
    }
    public function getMonthZhiExact()
    {
        return LunarUtil::$ZHI[$this->monthZhiIndexExact + 1];
    }
    public function getDayInGanZhi()
    {
        return $this->getDayGan() . $this->getDayZhi();
    }
    public function getDayInGanZhiExact()
    {
        return $this->getDayGanExact() . $this->getDayZhiExact();
    }
    public function getDayInGanZhiExact2()
    {
        return $this->getDayGanExact2() . $this->getDayZhiExact2();
    }
    public function getDayGan()
    {
        return LunarUtil::$GAN[$this->dayGanIndex + 1];
    }
    public function getDayGanExact()
    {
        return LunarUtil::$GAN[$this->dayGanIndexExact + 1];
    }
    public function getDayGanExact2()
    {
        return LunarUtil::$GAN[$this->dayGanIndexExact2 + 1];
    }
    public function getDayZhi()
    {
        return LunarUtil::$ZHI[$this->dayZhiIndex + 1];
    }
    public function getDayZhiExact()
    {
        return LunarUtil::$ZHI[$this->dayZhiIndexExact + 1];
    }
    public function getDayZhiExact2()
    {
        return LunarUtil::$ZHI[$this->dayZhiIndexExact2 + 1];
    }
    public function getYearShengXiao()
    {
        return LunarUtil::$SHENG_XIAO[$this->yearZhiIndex + 1];
    }
    public function getYearShengXiaoByLiChun()
    {
        return LunarUtil::$SHENG_XIAO[$this->yearZhiIndexByLiChun + 1];
    }
    public function getYearShengXiaoExact()
    {
        return LunarUtil::$SHENG_XIAO[$this->yearZhiIndexExact + 1];
    }
    public function getMonthShengXiao()
    {
        return LunarUtil::$SHENG_XIAO[$this->monthZhiIndex + 1];
    }
    public function getDayShengXiao()
    {
        return LunarUtil::$SHENG_XIAO[$this->dayZhiIndex + 1];
    }
    public function getTimeShengXiao()
    {
        return LunarUtil::$SHENG_XIAO[$this->timeZhiIndex + 1];
    }
    public function getYearInChinese()
    {
        $y = $this->year . '';
        $s = '';
        for ($i = 0, $j = strlen($y); $i < $j; $i++) {
            $s .= LunarUtil::$NUMBER[ord(substr($y, $i, 1)) - 48];
        }
        return $s;
    }
    public function getMonthInChinese()
    {
        return ($this->month < 0 ? '闰' : '') . LunarUtil::$MONTH[abs($this->month)];
    }
    public function getDayInChinese()
    {
        return LunarUtil::$DAY[$this->day];
    }
    public function getTimeZhi()
    {
        return LunarUtil::$ZHI[$this->timeZhiIndex + 1];
    }
    public function getTimeGan()
    {
        return LunarUtil::$GAN[$this->timeGanIndex + 1];
    }
    public function getTimeInGanZhi()
    {
        return $this->getTimeGan() . $this->getTimeZhi();
    }
    public function getSeason()
    {
        return LunarUtil::$SEASON[abs($this->month)];
    }
    private function convertJieQi($name)
    {
        $jq = $name;
        if (strcmp('DONG_ZHI', $jq) === 0) {
            $jq = '冬至';
        } else if (strcmp('DA_HAN', $jq) === 0) {
            $jq = '大寒';
        } else if (strcmp('XIAO_HAN', $jq) === 0) {
            $jq = '小寒';
        } else if (strcmp('LI_CHUN', $jq) === 0) {
            $jq = '立春';
        } else if (strcmp('DA_XUE', $jq) === 0) {
            $jq = '大雪';
        } else if (strcmp('YU_SHUI', $jq) === 0) {
            $jq = '雨水';
        } else if (strcmp('JING_ZHE', $jq) === 0) {
            $jq = '惊蛰';
        }
        return $jq;
    }
    public function getJie()
    {
        for ($i = 0, $j = count(self::$JIE_QI_IN_USE); $i < $j; $i += 2) {
            $key = self::$JIE_QI_IN_USE[$i];
            $d = $this->jieQi[$key];
            if ($d->getYear() === $this->solar->getYear() && $d->getMonth() === $this->solar->getMonth() && $d->getDay() === $this->solar->getDay()) {
                return $this->convertJieQi($key);
            }
        }
        return '';
    }
    public function getQi()
    {
        for ($i = 1, $j = count(self::$JIE_QI_IN_USE); $i < $j; $i += 2) {
            $key = self::$JIE_QI_IN_USE[$i];
            $d = $this->jieQi[$key];
            if ($d->getYear() === $this->solar->getYear() && $d->getMonth() === $this->solar->getMonth() && $d->getDay() === $this->solar->getDay()) {
                return $this->convertJieQi($key);
            }
        }
        return '';
    }
    public function getWeek()
    {
        return $this->weekIndex;
    }
    public function getWeekInChinese()
    {
        return SolarUtil::$WEEK[$this->getWeek()];
    }
    public function getXiu()
    {
        return LunarUtil::$XIU[$this->getDayZhi() . $this->getWeek()];
    }
    public function getXiuLuck()
    {
        return LunarUtil::$XIU_LUCK[$this->getXiu()];
    }
    public function getXiuSong()
    {
        return LunarUtil::$XIU_SONG[$this->getXiu()];
    }
    public function getZheng()
    {
        return LunarUtil::$ZHENG[$this->getXiu()];
    }
    public function getAnimal()
    {
        return LunarUtil::$ANIMAL[$this->getXiu()];
    }
    public function getGong()
    {
        return LunarUtil::$GONG[$this->getXiu()];
    }
    public function getShou()
    {
        return LunarUtil::$SHOU[$this->getGong()];
    }
    public function getFestivals()
    {
        $l = array();
        $key = $this->month . '-' . $this->day;
        if (!empty(LunarUtil::$FESTIVAL[$key])) {
            $l[] = LunarUtil::$FESTIVAL[$key];
        }
        if (abs($this->month) === 12 && $this->day >= 29 && $this->year != $this->next(1)->getYear()) {
            $l[] = '除夕';
        }
        return $l;
    }
    public function getOtherFestivals()
    {
        $l = array();
        $key = $this->month . '-' . $this->day;
        if (!empty(LunarUtil::$OTHER_FESTIVAL[$key])) {
            foreach (LunarUtil::$OTHER_FESTIVAL[$key] as $f) {
                $l[] = $f;
            }
        }
        $jq = $this->jieQi['清明'];
        $solarYmd = $this->solar->toYmd();
        if (strcmp($solarYmd, $jq->next(-1)->toYmd()) === 0) {
            $l[] = '寒食节';
        }
        $jq = $this->jieQi['立春'];
        $offset = 4 - $jq->getLunar()->getDayGanIndex();
        if ($offset < 0) {
            $offset += 10;
        }
        if (strcmp($solarYmd, $jq->next($offset + 40)->toYmd()) === 0) {
            $l[] = '春社';
        }
        $jq = $this->jieQi['立秋'];
        $offset = 4 - $jq->getLunar()->getDayGanIndex();
        if ($offset < 0) {
            $offset += 10;
        }
        if (strcmp($solarYmd, $jq->next($offset + 40)->toYmd()) === 0) {
            $l[] = '秋社';
        }
        return $l;
    }
    public function getPengZuGan()
    {
        return LunarUtil::$PENG_ZU_GAN[$this->dayGanIndex + 1];
    }
    public function getPengZuZhi()
    {
        return LunarUtil::$PENG_ZU_ZHI[$this->dayZhiIndex + 1];
    }
    public function getPositionXi()
    {
        return $this->getDayPositionXi();
    }
    public function getPositionXiDesc()
    {
        return $this->getDayPositionXiDesc();
    }
    public function getDayPositionXi()
    {
        return LunarUtil::$POSITION_XI[$this->dayGanIndex + 1];
    }
    public function getDayPositionXiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getDayPositionXi()];
    }
    public function getTimePositionXi()
    {
        return LunarUtil::$POSITION_XI[$this->timeGanIndex + 1];
    }
    public function getTimePositionXiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getTimePositionXi()];
    }
    public function getPositionYangGui()
    {
        return $this->getDayPositionYangGui();
    }
    public function getPositionYangGuiDesc()
    {
        return $this->getDayPositionYangGuiDesc();
    }
    public function getDayPositionYangGui()
    {
        return LunarUtil::$POSITION_YANG_GUI[$this->dayGanIndex + 1];
    }
    public function getDayPositionYangGuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getDayPositionYangGui()];
    }
    public function getTimePositionYangGui()
    {
        return LunarUtil::$POSITION_YANG_GUI[$this->timeGanIndex + 1];
    }
    public function getTimePositionYangGuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getTimePositionYangGui()];
    }
    public function getPositionYinGui()
    {
        return $this->getDayPositionYinGui();
    }
    public function getPositionYinGuiDesc()
    {
        return $this->getDayPositionYinGuiDesc();
    }
    public function getDayPositionYinGui()
    {
        return LunarUtil::$POSITION_YIN_GUI[$this->dayGanIndex + 1];
    }
    public function getDayPositionYinGuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getDayPositionYinGui()];
    }
    public function getTimePositionYinGui()
    {
        return LunarUtil::$POSITION_YIN_GUI[$this->timeGanIndex + 1];
    }
    public function getTimePositionYinGuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getTimePositionYinGui()];
    }
    public function getPositionFu()
    {
        return $this->getDayPositionFu();
    }
    public function getPositionFuDesc()
    {
        return $this->getDayPositionFuDesc();
    }
    public function getDayPositionFu($sect = 2)
    {
        $fu = 1 == $sect ? LunarUtil::$POSITION_FU : LunarUtil::$POSITION_FU_2;
        return $fu[$this->dayGanIndex + 1];
    }
    public function getDayPositionFuBySect($sect)
    {
        $fu = 1 == $sect ? LunarUtil::$POSITION_FU : LunarUtil::$POSITION_FU_2;
        return $fu[$this->dayGanIndex + 1];
    }
    public function getDayPositionFuDesc($sect = 2)
    {
        return LunarUtil::$POSITION_DESC[$this->getDayPositionFu($sect)];
    }
    public function getDayPositionFuDescBySect($sect)
    {
        return LunarUtil::$POSITION_DESC[$this->getDayPositionFu($sect)];
    }
    public function getTimePositionFu($sect = 2)
    {
        $fu = 1 == $sect ? LunarUtil::$POSITION_FU : LunarUtil::$POSITION_FU_2;
        return $fu[$this->timeGanIndex + 1];
    }
    public function getTimePositionFuBySect($sect)
    {
        $fu = 1 == $sect ? LunarUtil::$POSITION_FU : LunarUtil::$POSITION_FU_2;
        return $fu[$this->timeGanIndex + 1];
    }
    public function getTimePositionFuDesc($sect = 2)
    {
        return LunarUtil::$POSITION_DESC[$this->getTimePositionFu($sect)];
    }
    public function getTimePositionFuDescBySect($sect)
    {
        return LunarUtil::$POSITION_DESC[$this->getTimePositionFu($sect)];
    }
    public function getPositionCai()
    {
        return $this->getDayPositionCai();
    }
    public function getPositionCaiDesc()
    {
        return $this->getDayPositionCaiDesc();
    }
    public function getDayPositionCai()
    {
        return LunarUtil::$POSITION_CAI[$this->dayGanIndex + 1];
    }
    public function getDayPositionCaiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getDayPositionCai()];
    }
    public function getTimePositionCai()
    {
        return LunarUtil::$POSITION_CAI[$this->timeGanIndex + 1];
    }
    public function getTimePositionCaiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getTimePositionCai()];
    }
    public function _getYearPositionTaiSuiBySect($sect)
    {
        switch ($sect) {
            case 1:
                $yearZhiIndex = $this->yearZhiIndex;
                break;
            case 3:
                $yearZhiIndex = $this->yearZhiIndexExact;
                break;
            default:
                $yearZhiIndex = $this->yearZhiIndexByLiChun;
        }
        return LunarUtil::$POSITION_TAI_SUI_YEAR[$yearZhiIndex];
    }
    public function getYearPositionTaiSui($sect = 2)
    {
        return $this->_getYearPositionTaiSuiBySect($sect);
    }
    public function getYearPositionTaiSuiBySect($sect)
    {
        return $this->_getYearPositionTaiSuiBySect($sect);
    }
    public function getYearPositionTaiSuiDesc($sect = 2)
    {
        return LunarUtil::$POSITION_DESC[$this->getYearPositionTaiSui($sect)];
    }
    public function getYearPositionTaiSuiDescBySect($sect)
    {
        return LunarUtil::$POSITION_DESC[$this->getYearPositionTaiSui($sect)];
    }
    protected function _getMonthPositionTaiSui($monthZhiIndex, $monthGanIndex)
    {
        $m = $monthZhiIndex - LunarUtil::$BASE_MONTH_ZHI_INDEX;
        if ($m < 0) {
            $m += 12;
        }
        switch ($m) {
            case 0:
            case 4:
            case 8:
                $p = '艮';
                break;
            case 2:
            case 6:
            case 10:
                $p = '坤';
                break;
            case 3:
            case 7:
            case 11:
                $p = '巽';
                break;
            default:
                $p = LunarUtil::$POSITION_GAN[$monthGanIndex];
        }
        return $p;
    }
    public function _getMonthPositionTaiSuiBySect($sect)
    {
        switch ($sect) {
            case 3:
                $monthZhiIndex = $this->monthZhiIndexExact;
                $monthGanIndex = $this->monthGanIndexExact;
                break;
            default:
                $monthZhiIndex = $this->monthZhiIndex;
                $monthGanIndex = $this->monthGanIndex;
        }
        return $this->_getMonthPositionTaiSui($monthZhiIndex, $monthGanIndex);
    }
    public function getMonthPositionTaiSuiBySect($sect)
    {
        return $this->_getMonthPositionTaiSuiBySect($sect);
    }
    public function getMonthPositionTaiSui($sect = 2)
    {
        return $this->_getMonthPositionTaiSuiBySect($sect);
    }
    public function getMonthPositionTaiSuiDesc($sect = 2)
    {
        return LunarUtil::$POSITION_DESC[$this->getMonthPositionTaiSui($sect)];
    }
    public function getMonthPositionTaiSuiDescBySect($sect)
    {
        return LunarUtil::$POSITION_DESC[$this->getMonthPositionTaiSui($sect)];
    }
    protected function _getDayPositionTaiSui($dayInGanZhi, $yearZhiIndex)
    {
        if (strpos('甲子,乙丑,丙寅,丁卯,戊辰,己巳', $dayInGanZhi) !== false) {
            $p = '震';
        } else if (strpos('丙子,丁丑,戊寅,己卯,庚辰,辛巳', $dayInGanZhi) !== false) {
            $p = '离';
        } else if (strpos('戊子,己丑,庚寅,辛卯,壬辰,癸巳', $dayInGanZhi) !== false) {
            $p = '中';
        } else if (strpos('庚子,辛丑,壬寅,癸卯,甲辰,乙巳', $dayInGanZhi) !== false) {
            $p = '兑';
        } else if (strpos('壬子,癸丑,甲寅,乙卯,丙辰,丁巳', $dayInGanZhi) !== false) {
            $p = '坎';
        } else {
            $p = LunarUtil::$POSITION_TAI_SUI_YEAR[$yearZhiIndex];
        }
        return $p;
    }
    public function _getDayPositionTaiSuiBySect($sect)
    {
        switch ($sect) {
            case 1:
                $dayInGanZhi = $this->getDayInGanZhi();
                $yearZhiIndex = $this->yearZhiIndex;
                break;
            case 3:
                $dayInGanZhi = $this->getDayInGanZhi();
                $yearZhiIndex = $this->yearZhiIndexExact;
                break;
            default:
                $dayInGanZhi = $this->getDayInGanZhiExact2();
                $yearZhiIndex = $this->yearZhiIndexByLiChun;
        }
        return $this->_getDayPositionTaiSui($dayInGanZhi, $yearZhiIndex);
    }
    public function getDayPositionTaiSuiBySect($sect)
    {
        return $this->_getDayPositionTaiSuiBySect($sect);
    }
    public function getDayPositionTaiSui($sect = 2)
    {
        return $this->_getDayPositionTaiSuiBySect($sect);
    }
    public function getDayPositionTaiSuiDesc($sect = 2)
    {
        return LunarUtil::$POSITION_DESC[$this->getDayPositionTaiSui($sect)];
    }
    public function getDayPositionTaiSuiDescBySect($sect)
    {
        return LunarUtil::$POSITION_DESC[$this->getDayPositionTaiSui($sect)];
    }
    public function getChong()
    {
        return $this->getDayChong();
    }
    public function getDayChong()
    {
        return LunarUtil::$CHONG[$this->dayZhiIndex];
    }
    public function getTimeChong()
    {
        return LunarUtil::$CHONG[$this->timeZhiIndex];
    }
    public function getChongGan()
    {
        return $this->getDayChongGan();
    }
    public function getDayChongGan()
    {
        return LunarUtil::$CHONG_GAN[$this->dayGanIndex];
    }
    public function getTimeChongGan()
    {
        return LunarUtil::$CHONG_GAN[$this->timeGanIndex];
    }
    public function getChongGanTie()
    {
        return $this->getDayChongGanTie();
    }
    public function getDayChongGanTie()
    {
        return LunarUtil::$CHONG_GAN_TIE[$this->dayGanIndex];
    }
    public function getTimeChongGanTie()
    {
        return LunarUtil::$CHONG_GAN_TIE[$this->timeGanIndex];
    }
    public function getChongShengXiao()
    {
        return $this->getDayChongShengXiao();
    }
    public function getDayChongShengXiao()
    {
        $chong = $this->getDayChong();
        for ($i = 0, $j = count(LunarUtil::$ZHI); $i < $j; $i++) {
            if (strcmp(LunarUtil::$ZHI[$i], $chong) === 0) {
                return LunarUtil::$SHENG_XIAO[$i];
            }
        }
        return '';
    }
    public function getTimeChongShengXiao()
    {
        $chong = $this->getTimeChong();
        for ($i = 0, $j = count(LunarUtil::$ZHI); $i < $j; $i++) {
            if (strcmp(LunarUtil::$ZHI[$i], $chong) === 0) {
                return LunarUtil::$SHENG_XIAO[$i];
            }
        }
        return '';
    }
    public function getChongDesc()
    {
        return $this->getDayChongDesc();
    }
    public function getDayChongDesc()
    {
        return '(' . $this->getDayChongGan() . $this->getDayChong() . ')' . $this->getDayChongShengXiao();
    }
    public function getTimeChongDesc()
    {
        return '(' . $this->getTimeChongGan() . $this->getTimeChong() . ')' . $this->getTimeChongShengXiao();
    }
    public function getSha()
    {
        return $this->getDaySha();
    }
    public function getDaySha()
    {
        return LunarUtil::$SHA[$this->getDayZhi()];
    }
    public function getTimeSha()
    {
        return LunarUtil::$SHA[$this->getTimeZhi()];
    }
    public function getYearNaYin()
    {
        return LunarUtil::$NAYIN[$this->getYearInGanZhi()];
    }
    public function getMonthNaYin()
    {
        return LunarUtil::$NAYIN[$this->getMonthInGanZhi()];
    }
    public function getDayNaYin()
    {
        return LunarUtil::$NAYIN[$this->getDayInGanZhi()];
    }
    public function getTimeNaYin()
    {
        return LunarUtil::$NAYIN[$this->getTimeInGanZhi()];
    }
    public function getBaZi()
    {
        $baZi = $this->getEightChar();
        $l = array();
        $l[] = $baZi->getYear();
        $l[] = $baZi->getMonth();
        $l[] = $baZi->getDay();
        $l[] = $baZi->getTime();
        return $l;
    }
    public function getBaZiWuXing()
    {
        $baZi = $this->getEightChar();
        $l = array();
        $l[] = $baZi->getYearWuXing();
        $l[] = $baZi->getMonthWuXing();
        $l[] = $baZi->getDayWuXing();
        $l[] = $baZi->getTimeWuXing();
        return $l;
    }
    public function getBaZiNaYin()
    {
        $baZi = $this->getEightChar();
        $l = array();
        $l[] = $baZi->getYearNaYin();
        $l[] = $baZi->getMonthNaYin();
        $l[] = $baZi->getDayNaYin();
        $l[] = $baZi->getTimeNaYin();
        return $l;
    }
    public function getBaZiShiShenGan()
    {
        $baZi = $this->getEightChar();
        $l = array();
        $l[] = $baZi->getYearShiShenGan();
        $l[] = $baZi->getMonthShiShenGan();
        $l[] = $baZi->getDayShiShenGan();
        $l[] = $baZi->getTimeShiShenGan();
        return $l;
    }
    public function getBaZiShiShenZhi()
    {
        $baZi = $this->getEightChar();
        $yearShiShenZhi = $baZi->getYearShiShenZhi();
        $monthShiShenZhi = $baZi->getMonthShiShenZhi();
        $dayShiShenZhi = $baZi->getDayShiShenZhi();
        $timeShiShenZhi = $baZi->getTimeShiShenZhi();
        $l = array();
        $l[] = $yearShiShenZhi[0];
        $l[] = $monthShiShenZhi[0];
        $l[] = $dayShiShenZhi[0];
        $l[] = $timeShiShenZhi[0];
        return $l;
    }
    public function getBaZiShiShenYearZhi()
    {
        return $this->getEightChar()->getYearShiShenZhi();
    }
    public function getBaZiShiShenMonthZhi()
    {
        return $this->getEightChar()->getMonthShiShenZhi();
    }
    public function getBaZiShiShenDayZhi()
    {
        return $this->getEightChar()->getDayShiShenZhi();
    }
    public function getBaZiShiShenTimeZhi()
    {
        return $this->getEightChar()->getTimeShiShenZhi();
    }
    public function getZhiXing()
    {
        $offset = $this->dayZhiIndex - $this->monthZhiIndex;
        if ($offset < 0) {
            $offset += 12;
        }
        return LunarUtil::$ZHI_XING[$offset + 1];
    }
    public function getDayTianShen()
    {
        return LunarUtil::$TIAN_SHEN[($this->dayZhiIndex + LunarUtil::$ZHI_TIAN_SHEN_OFFSET[$this->getMonthZhi()]) % 12 + 1];
    }
    public function getTimeTianShen()
    {
        return LunarUtil::$TIAN_SHEN[($this->timeZhiIndex + LunarUtil::$ZHI_TIAN_SHEN_OFFSET[$this->getDayZhiExact()]) % 12 + 1];
    }
    public function getDayTianShenType()
    {
        return LunarUtil::$TIAN_SHEN_TYPE[$this->getDayTianShen()];
    }
    public function getTimeTianShenType()
    {
        return LunarUtil::$TIAN_SHEN_TYPE[$this->getTimeTianShen()];
    }
    public function getDayTianShenLuck()
    {
        return LunarUtil::$TIAN_SHEN_TYPE_LUCK[$this->getDayTianShenType()];
    }
    public function getTimeTianShenLuck()
    {
        return LunarUtil::$TIAN_SHEN_TYPE_LUCK[$this->getTimeTianShenType()];
    }
    public function getDayPositionTai()
    {
        return LunarUtil::$POSITION_TAI_DAY[LunarUtil::getJiaZiIndex($this->getDayInGanZhi())];
    }
    public function getMonthPositionTai()
    {
        if ($this->month < 0) {
            return '';
        }
        return LunarUtil::$POSITION_TAI_MONTH[$this->month - 1];
    }
    public function getDayYi($sect = 1)
    {
        return LunarUtil::getDayYi(2 == $sect ? $this->getMonthInGanZhiExact() : $this->getMonthInGanZhi(), $this->getDayInGanZhi());
    }
    public function getTimeYi()
    {
        return LunarUtil::getTimeYi($this->getDayInGanZhiExact(), $this->getTimeInGanZhi());
    }
    public function getDayJi($sect = 1)
    {
        return LunarUtil::getDayJi(2 == $sect ? $this->getMonthInGanZhiExact() : $this->getMonthInGanZhi(), $this->getDayInGanZhi());
    }
    public function getTimeJi()
    {
        return LunarUtil::getTimeJi($this->getDayInGanZhiExact(), $this->getTimeInGanZhi());
    }
    public function getDayJiShen()
    {
        return LunarUtil::getDayJiShen($this->getMonth(), $this->getDayInGanZhi());
    }
    public function getDayXiongSha()
    {
        return LunarUtil::getDayXiongSha($this->getMonth(), $this->getDayInGanZhi());
    }
    public function getYueXiang()
    {
        return LunarUtil::$YUE_XIANG[$this->getDay()];
    }
    protected function _getYearNineStar($yearInGanZhi)
    {
        $indexExact = LunarUtil::getJiaZiIndex($yearInGanZhi) + 1;
        $index = LunarUtil::getJiaZiIndex($this->getYearInGanZhi()) + 1;
        $yearOffset = $indexExact - $index;
        if ($yearOffset > 1) {
            $yearOffset -= 60;
        } else if ($yearOffset < -1) {
            $yearOffset += 60;
        }
        $yuan = (int)(($this->year + $yearOffset + 2696) / 60) % 3;
        $offset = (62 + $yuan * 3 - $indexExact) % 9;
        if (0 === $offset) {
            $offset = 9;
        }
        return NineStar::fromIndex($offset - 1);
    }
    public function _getYearNineStarBySect($sect)
    {
        switch ($sect) {
            case 1:
                $yearInGanZhi = $this->getYearInGanZhi();
                break;
            case 3:
                $yearInGanZhi = $this->getYearInGanZhiExact();
                break;
            default:
                $yearInGanZhi = $this->getYearInGanZhiByLiChun();
        }
        return $this->_getYearNineStar($yearInGanZhi);
    }
    public function getYearNineStarBySect($sect)
    {
        return $this->_getYearNineStarBySect($sect);
    }
    public function getYearNineStar($sect = 2)
    {
        return $this->_getYearNineStarBySect($sect);
    }
    public function _getMonthNineStar($yearZhiIndex, $monthZhiIndex)
    {
        $index = $yearZhiIndex % 3;
        $n = 27 - ($index * 3);
        if ($monthZhiIndex < LunarUtil::$BASE_MONTH_ZHI_INDEX) {
            $n -= 3;
        }
        $offset = ($n - $monthZhiIndex) % 9;
        return NineStar::fromIndex($offset);
    }
    public function _getMonthNineStarBySect($sect)
    {
        switch ($sect) {
            case 1:
                $yearZhiIndex = $this->yearZhiIndex;
                $monthZhiIndex = $this->monthZhiIndex;
                break;
            case 3:
                $yearZhiIndex = $this->yearZhiIndexExact;
                $monthZhiIndex = $this->monthZhiIndexExact;
                break;
            default:
                $yearZhiIndex = $this->yearZhiIndexByLiChun;
                $monthZhiIndex = $this->monthZhiIndex;
        }
        return $this->_getMonthNineStar($yearZhiIndex, $monthZhiIndex);
    }
    public function getMonthNineStarBySect($sect)
    {
        return $this->_getMonthNineStarBySect($sect);
    }
    public function getMonthNineStar($sect = 2)
    {
        return $this->_getMonthNineStarBySect($sect);
    }
    public function getDayNineStar()
    {
        $solarYmd = $this->solar->toYmd();
        $dongZhi = $this->jieQi['冬至'];
        $dongZhi2 = $this->jieQi['DONG_ZHI'];
        $xiaZhi = $this->jieQi['夏至'];
        $dongZhiIndex = LunarUtil::getJiaZiIndex($dongZhi->getLunar()->getDayInGanZhi());
        $dongZhiIndex2 = LunarUtil::getJiaZiIndex($dongZhi2->getLunar()->getDayInGanZhi());
        $xiaZhiIndex = LunarUtil::getJiaZiIndex($xiaZhi->getLunar()->getDayInGanZhi());
        if ($dongZhiIndex > 29) {
            $solarShunBai = $dongZhi->next(60 - $dongZhiIndex);
        } else {
            $solarShunBai = $dongZhi->next(-$dongZhiIndex);
        }
        $solarShunBaiYmd = $solarShunBai->toYmd();
        if ($dongZhiIndex2 > 29) {
            $solarShunBai2 = $dongZhi2->next(60 - $dongZhiIndex2);
        } else {
            $solarShunBai2 = $dongZhi2->next(-$dongZhiIndex2);
        }
        $solarShunBaiYmd2 = $solarShunBai2->toYmd();
        if ($xiaZhiIndex > 29) {
            $solarNiZi = $xiaZhi->next(60 - $xiaZhiIndex);
        } else {
            $solarNiZi = $xiaZhi->next(-$xiaZhiIndex);
        }
        $solarNiZiYmd = $solarNiZi->toYmd();
        $offset = 0;
        if (strcmp($solarYmd, $solarShunBaiYmd) >= 0 && strcmp($solarYmd, $solarNiZiYmd) < 0) {
            $offset = $this->solar->subtract($solarShunBai) % 9;
        } else if (strcmp($solarYmd, $solarNiZiYmd) >= 0 && strcmp($solarYmd, $solarShunBaiYmd2) < 0) {
            $offset = 8 - ($this->solar->subtract($solarNiZi) % 9);
        } else if (strcmp($solarYmd, $solarShunBaiYmd2) >= 0) {
            $offset = $this->solar->subtract($solarShunBai2) % 9;
        } else if (strcmp($solarYmd, $solarShunBaiYmd) < 0) {
            $offset = (8 + $solarShunBai->subtract($this->solar)) % 9;
        }
        return NineStar::fromIndex($offset);
    }
    public function getTimeNineStar()
    {
        $solarYmd = $this->solar->toYmd();
        $asc = false;
        if (strcmp($solarYmd, $this->jieQi['冬至']->toYmd()) >= 0 && strcmp($solarYmd, $this->jieQi['夏至']->toYmd()) < 0) {
            $asc = true;
        } else if (strcmp($solarYmd, $this->jieQi['DONG_ZHI']->toYmd()) >= 0) {
            $asc = true;
        }
        $start = $asc ? 6 : 2;
        $dayZhi = $this->getDayZhi();
        if (strpos('子午卯酉', $dayZhi) !== false) {
            $start = $asc ? 0 : 8;
        } else if (strpos('辰戌丑未', $dayZhi) !== false) {
            $start = $asc ? 3 : 5;
        }
        $index = $asc ? $start + $this->timeZhiIndex : $start + 9 - $this->timeZhiIndex;
        return NineStar::fromIndex($index % 9);
    }
    public function getJieQiTable()
    {
        return $this->jieQi;
    }
    protected function getNearJieQi($forward, $conditions, $wholeDay)
    {
        $name = null;
        $near = null;
        $filter = null != $conditions && count($conditions) > 0;
        $today = $wholeDay ? $this->solar->toYmd() : $this->solar->toYmdHms();
        foreach ($this->jieQi as $key => $solar) {
            $jq = $this->convertJieQi($key);
            if ($filter) {
                if (!in_array($jq, $conditions)) {
                    continue;
                }
            }
            $day = $wholeDay ? $solar->toYmd() : $solar->toYmdHms();
            if ($forward) {
                if (strcmp($day, $today) <= 0) {
                    continue;
                }
                if (null == $near) {
                    $name = $jq;
                    $near = $solar;
                } else {
                    $nearDay = $wholeDay ? $near->toYmd() : $near->toYmdHms();
                    if (strcmp($day, $nearDay) < 0) {
                        $name = $jq;
                        $near = $solar;
                    }
                }
            } else {
                if (strcmp($day, $today) > 0) {
                    continue;
                }
                if (null == $near) {
                    $name = $jq;
                    $near = $solar;
                } else {
                    $nearDay = $wholeDay ? $near->toYmd() : $near->toYmdHms();
                    if (strcmp($day, $nearDay) > 0) {
                        $name = $jq;
                        $near = $solar;
                    }
                }
            }
        }
        if (null == $near) {
            return null;
        }
        return new JieQi($name, $near);
    }
    public function getNextJieByWholeDay($wholeDay)
    {
        $conditions = array();
        for ($i = 0, $j = count(self::$JIE_QI_IN_USE) / 2; $i < $j; $i++) {
            $conditions[] = self::$JIE_QI_IN_USE[$i * 2];
        }
        return $this->getNearJieQi(true, $conditions, $wholeDay);
    }
    public function getNextJie()
    {
        return $this->getNextJieByWholeDay(false);
    }
    public function getPrevJieByWholeDay($wholeDay)
    {
        $conditions = array();
        for ($i = 0, $j = count(self::$JIE_QI_IN_USE) / 2; $i < $j; $i++) {
            $conditions[] = self::$JIE_QI_IN_USE[$i * 2];
        }
        return $this->getNearJieQi(false, $conditions, $wholeDay);
    }
    public function getPrevJie()
    {
        return $this->getPrevJieByWholeDay(false);
    }
    public function getNextQiByWholeDay($wholeDay)
    {
        $conditions = array();
        for ($i = 0, $j = count(self::$JIE_QI_IN_USE) / 2; $i < $j; $i++) {
            $conditions[] = self::$JIE_QI_IN_USE[$i * 2 + 1];
        }
        return $this->getNearJieQi(true, $conditions, $wholeDay);
    }
    public function getNextQi()
    {
        return $this->getNextQiByWholeDay(false);
    }
    public function getPrevQiByWholeDay($wholeDay)
    {
        $conditions = array();
        for ($i = 0, $j = count(self::$JIE_QI_IN_USE) / 2; $i < $j; $i++) {
            $conditions[] = self::$JIE_QI_IN_USE[$i * 2 + 1];
        }
        return $this->getNearJieQi(false, $conditions, $wholeDay);
    }
    public function getPrevQi()
    {
        return $this->getPrevQiByWholeDay(false);
    }
    public function getNextJieQiByWholeDay($wholeDay)
    {
        return $this->getNearJieQi(true, null, $wholeDay);
    }
    public function getNextJieQi()
    {
        return $this->getNextJieQiByWholeDay(false);
    }
    public function getPrevJieQiByWholeDay($wholeDay)
    {
        return $this->getNearJieQi(false, null, $wholeDay);
    }
    public function getPrevJieQi()
    {
        return $this->getPrevJieQiByWholeDay(false);
    }
    public function getJieQi()
    {
        foreach ($this->jieQi as $key => $d) {
            if ($d->getYear() === $this->solar->getYear() && $d->getMonth() === $this->solar->getMonth() && $d->getDay() === $this->solar->getDay()) {
                return $this->convertJieQi($key);
            }
        }
        return '';
    }
    public function getCurrentJieQi()
    {
        foreach ($this->jieQi as $key => $d) {
            if ($d->getYear() === $this->solar->getYear() && $d->getMonth() === $this->solar->getMonth() && $d->getDay() === $this->solar->getDay()) {
                return new JieQi($key, $d);
            }
        }
        return null;
    }
    public function getCurrentJie()
    {
        for ($i = 0, $j = count(self::$JIE_QI_IN_USE); $i < $j; $i += 2) {
            $key = self::$JIE_QI_IN_USE[$i];
            $d = $this->jieQi[$key];
            if ($d->getYear() === $this->solar->getYear() && $d->getMonth() === $this->solar->getMonth() && $d->getDay() === $this->solar->getDay()) {
                return new JieQi($key, $d);
            }
        }
        return null;
    }
    public function getCurrentQi()
    {
        for ($i = 1, $j = count(self::$JIE_QI_IN_USE); $i < $j; $i += 2) {
            $key = self::$JIE_QI_IN_USE[$i];
            $d = $this->jieQi[$key];
            if ($d->getYear() === $this->solar->getYear() && $d->getMonth() === $this->solar->getMonth() && $d->getDay() === $this->solar->getDay()) {
                return new JieQi($key, $d);
            }
        }
        return null;
    }
    public function getTimeGanIndex()
    {
        return $this->timeGanIndex;
    }
    public function getTimeZhiIndex()
    {
        return $this->timeZhiIndex;
    }
    public function getDayGanIndex()
    {
        return $this->dayGanIndex;
    }
    public function getDayZhiIndex()
    {
        return $this->dayZhiIndex;
    }
    public function getMonthGanIndex()
    {
        return $this->monthGanIndex;
    }
    public function getMonthZhiIndex()
    {
        return $this->monthZhiIndex;
    }
    public function getYearGanIndex()
    {
        return $this->yearGanIndex;
    }
    public function getYearZhiIndex()
    {
        return $this->yearZhiIndex;
    }
    public function getYearGanIndexByLiChun()
    {
        return $this->yearGanIndexByLiChun;
    }
    public function getYearZhiIndexByLiChun()
    {
        return $this->yearZhiIndexByLiChun;
    }
    public function getDayGanIndexExact()
    {
        return $this->dayGanIndexExact;
    }
    public function getDayGanIndexExact2()
    {
        return $this->dayGanIndexExact2;
    }
    public function getDayZhiIndexExact()
    {
        return $this->dayZhiIndexExact;
    }
    public function getDayZhiIndexExact2()
    {
        return $this->dayZhiIndexExact2;
    }
    public function getMonthGanIndexExact()
    {
        return $this->monthGanIndexExact;
    }
    public function getMonthZhiIndexExact()
    {
        return $this->monthZhiIndexExact;
    }
    public function getYearGanIndexExact()
    {
        return $this->yearGanIndexExact;
    }
    public function getYearZhiIndexExact()
    {
        return $this->yearZhiIndexExact;
    }
    public function getEightChar()
    {
        if (null == $this->eightChar) {
            $this->eightChar = EightChar::fromLunar($this);
        }
        return $this->eightChar;
    }
    public function next($days)
    {
        return $this->solar->next($days)->getLunar();
    }
    public function toFullString()
    {
        $s = '';
        $s .= $this;
        $s .= ' ';
        $s .= $this->getYearInGanZhi();
        $s .= '(';
        $s .= $this->getYearShengXiao();
        $s .= ')年 ';
        $s .= $this->getMonthInGanZhi();
        $s .= '(';
        $s .= $this->getMonthShengXiao();
        $s .= ')月 ';
        $s .= $this->getDayInGanZhi();
        $s .= '(';
        $s .= $this->getDayShengXiao();
        $s .= ')日 ';
        $s .= $this->getTimeZhi();
        $s .= '(';
        $s .= $this->getTimeShengXiao();
        $s .= ')时 纳音[';
        $s .= $this->getYearNaYin();
        $s .= ' ';
        $s .= $this->getMonthNaYin();
        $s .= ' ';
        $s .= $this->getDayNaYin();
        $s .= ' ';
        $s .= $this->getTimeNaYin();
        $s .= '] 星期';
        $s .= $this->getWeekInChinese();
        foreach ($this->getFestivals() as $f) {
            $s .= ' (' . $f . ')';
        }
        foreach ($this->getOtherFestivals() as $f) {
            $s .= ' (' . $f . ')';
        }
        $jq = $this->getJieQi();
        if (strlen($jq) > 0) {
            $s .= ' (' . $jq . ')';
        }
        $s .= ' ';
        $s .= $this->getGong();
        $s .= '方';
        $s .= $this->getShou();
        $s .= ' 星宿[';
        $s .= $this->getXiu();
        $s .= $this->getZheng();
        $s .= $this->getAnimal();
        $s .= '](';
        $s .= $this->getXiuLuck();
        $s .= ') 彭祖百忌[';
        $s .= $this->getPengZuGan();
        $s .= ' ';
        $s .= $this->getPengZuZhi();
        $s .= '] 喜神方位[';
        $s .= $this->getDayPositionXi();
        $s .= '](';
        $s .= $this->getDayPositionXiDesc();
        $s .= ') 阳贵神方位[';
        $s .= $this->getDayPositionYangGui();
        $s .= '](';
        $s .= $this->getDayPositionYangGuiDesc();
        $s .= ') 阴贵神方位[';
        $s .= $this->getDayPositionYinGui();
        $s .= '](';
        $s .= $this->getDayPositionYinGuiDesc();
        $s .= ') 福神方位[';
        $s .= $this->getDayPositionFu();
        $s .= '](';
        $s .= $this->getDayPositionFuDesc();
        $s .= ') 财神方位[';
        $s .= $this->getDayPositionCai();
        $s .= '](';
        $s .= $this->getDayPositionCaiDesc();
        $s .= ') 冲[';
        $s .= $this->getChongDesc();
        $s .= '] 煞[';
        $s .= $this->getSha();
        $s .= ']';
        return $s;
    }
    public function toString()
    {
        return $this->getYearInChinese() . '年' . $this->getMonthInChinese() . '月' . $this->getDayInChinese();
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function getYearXun()
    {
        return LunarUtil::getXun($this->getYearInGanZhi());
    }
    public function getYearXunByLiChun()
    {
        return LunarUtil::getXun($this->getYearInGanZhiByLiChun());
    }
    public function getYearXunExact()
    {
        return LunarUtil::getXun($this->getYearInGanZhiExact());
    }
    public function getYearXunKong()
    {
        return LunarUtil::getXunKong($this->getYearInGanZhi());
    }
    public function getYearXunKongByLiChun()
    {
        return LunarUtil::getXunKong($this->getYearInGanZhiByLiChun());
    }
    public function getYearXunKongExact()
    {
        return LunarUtil::getXunKong($this->getYearInGanZhiExact());
    }
    public function getMonthXun()
    {
        return LunarUtil::getXun($this->getMonthInGanZhi());
    }
    public function getMonthXunExact()
    {
        return LunarUtil::getXun($this->getMonthInGanZhiExact());
    }
    public function getMonthXunKong()
    {
        return LunarUtil::getXunKong($this->getMonthInGanZhi());
    }
    public function getMonthXunKongExact()
    {
        return LunarUtil::getXunKong($this->getMonthInGanZhiExact());
    }
    public function getDayXun()
    {
        return LunarUtil::getXun($this->getDayInGanZhi());
    }
    public function getDayXunExact()
    {
        return LunarUtil::getXun($this->getDayInGanZhiExact());
    }
    public function getDayXunExact2()
    {
        return LunarUtil::getXun($this->getDayInGanZhiExact2());
    }
    public function getDayXunKong()
    {
        return LunarUtil::getXunKong($this->getDayInGanZhi());
    }
    public function getDayXunKongExact()
    {
        return LunarUtil::getXunKong($this->getDayInGanZhiExact());
    }
    public function getDayXunKongExact2()
    {
        return LunarUtil::getXunKong($this->getDayInGanZhiExact2());
    }
    public function getTimeXun()
    {
        return LunarUtil::getXun($this->getTimeInGanZhi());
    }
    public function getTimeXunKong()
    {
        return LunarUtil::getXunKong($this->getTimeInGanZhi());
    }
    public function getShuJiu()
    {
        $current = Solar::fromYmd($this->solar->getYear(), $this->solar->getMonth(), $this->solar->getDay());
        $start = $this->jieQi['DONG_ZHI'];
        $start = Solar::fromYmd($start->getYear(), $start->getMonth(), $start->getDay());
        if ($current->isBefore($start)) {
            $start = $this->jieQi['冬至'];
            $start = Solar::fromYmd($start->getYear(), $start->getMonth(), $start->getDay());
        }
        $end = Solar::fromYmd($start->getYear(), $start->getMonth(), $start->getDay())->next(81);
        if ($current->isBefore($start) || (!$current->isBefore($end))) {
            return null;
        }
        $days = $current->subtract($start);
        return new ShuJiu(LunarUtil::$NUMBER[(int)($days / 9) + 1] . '九', $days % 9 + 1);
    }
    public function getFu()
    {
        $current = Solar::fromYmd($this->solar->getYear(), $this->solar->getMonth(), $this->solar->getDay());
        $xiaZhi = $this->jieQi['夏至'];
        $liQiu = $this->jieQi['立秋'];
        $start = Solar::fromYmd($xiaZhi->getYear(), $xiaZhi->getMonth(), $xiaZhi->getDay());
        $add = 6 - $xiaZhi->getLunar()->getDayGanIndex();
        if ($add < 0) {
            $add += 10;
        }
        $add += 20;
        $start = $start->next($add);
        if ($current->isBefore($start)) {
            return null;
        }
        $days = $current->subtract($start);
        if ($days < 10) {
            return new Fu('初伏', $days + 1);
        }
        $start = $start->next(10);
        $days = $current->subtract($start);
        if ($days < 10) {
            return new Fu('中伏', $days + 1);
        }
        $start = $start->next(10);
        $days = $current->subtract($start);
        $liQiuSolar = Solar::fromYmd($liQiu->getYear(), $liQiu->getMonth(), $liQiu->getDay());
        if ($liQiuSolar->isAfter($start)) {
            if ($days < 10) {
                return new Fu('中伏', $days + 11);
            }
            $start = $start->next(10);
            $days = $current->subtract($start);
        }
        if ($days < 10) {
            return new Fu('末伏', $days + 1);
        }
        return null;
    }
    public function getLiuYao()
    {
        return LunarUtil::$LIU_YAO[(abs($this->month) + $this->day - 2) % 6];
    }
    public function getWuHou()
    {
        $jieQi = $this->getPrevJieQiByWholeDay(true);
        $offset = 0;
        for ($i = 0, $j = count(self::$JIE_QI); $i < $j; $i++) {
            if (strcmp($jieQi->getName(), self::$JIE_QI[$i]) === 0) {
                $offset = $i;
                break;
            }
        }
        $index = (int)($this->solar->subtract($jieQi->getSolar()) / 5);
        if ($index > 2) {
            $index = 2;
        }
        return LunarUtil::$WU_HOU[($offset * 3 + $index) % count(LunarUtil::$WU_HOU)];
    }
    public function getHou()
    {
        $jieQi = $this->getPrevJieQiByWholeDay(true);
        $max = count(LunarUtil::$HOU) - 1;
        $offset = floor($this->solar->subtract($jieQi->getSolar()) / 5);
        if ($offset > $max) {
            $offset = $max;
        }
        return $jieQi->getName() . ' ' . LunarUtil::$HOU[$offset];
    }
    public function getDayLu()
    {
        $gan = LunarUtil::$LU[$this->getDayGan()];
        $zhi = null;
        if (!empty(LunarUtil::$LU[$this->getDayZhi()])) {
            $zhi = LunarUtil::$LU[$this->getDayZhi()];
        }
        $lu = $gan . '命互禄';
        if (null != $zhi) {
            $lu .= ' ' . $zhi . '命进禄';
        }
        return $lu;
    }
    public function getTime()
    {
        return LunarTime::fromYmdHms($this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second);
    }
    public function getTimes()
    {
        $l = array();
        $l[] = LunarTime::fromYmdHms($this->year, $this->month, $this->day, 0, 0, 0);
        for ($i = 0; $i < 12; $i++) {
            $l[] = LunarTime::fromYmdHms($this->year, $this->month, $this->day, ($i + 1) * 2 - 1, 0, 0);
        }
        return $l;
    }
    public function getFoto()
    {
        return Foto::fromLunar($this);
    }
    public function getTao()
    {
        return Tao::fromLunar($this);
    }
}
class LunarMonth
{
    private $year;
    private $month;
    private $dayCount;
    private $firstJulianDay;
    private $index;
    private $zhiIndex;
    function __construct($lunarYear, $lunarMonth, $dayCount, $firstJulianDay, $index)
    {
        $this->year = intval($lunarYear);
        $this->month = intval($lunarMonth);
        $this->dayCount = intval($dayCount);
        $this->firstJulianDay = $firstJulianDay;
        $this->index = $index;
        $this->zhiIndex = ($index - 1 + LunarUtil::$BASE_MONTH_ZHI_INDEX) % 12;
    }
    public function toString()
    {
        return $this->year . '.' . $this->month;
    }
    public function toFullString()
    {
        return $this->year . '年' . ($this->isLeap() ? '闰' : '') . abs($this->month) . '月(' . $this->dayCount . '天)';
    }
    public function __toString()
    {
        return $this->toString();
    }
    public static function fromYm($lunarYear, $lunarMonth)
    {
        return LunarYear::fromYear($lunarYear)->getMonth($lunarMonth);
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getMonth()
    {
        return $this->month;
    }
    public function getDayCount()
    {
        return $this->dayCount;
    }
    public function getFirstJulianDay()
    {
        return $this->firstJulianDay;
    }
    public function getIndex()
    {
        return $this->index;
    }
    public function getZhiIndex()
    {
        return $this->zhiIndex;
    }
    public function getGanIndex()
    {
        $offset = (LunarYear::fromYear($this->year)->getGanIndex() + 1) % 5 * 2;
        return ($this->index - 1 + $offset) % 10;
    }
    public function getGan()
    {
        return LunarUtil::$GAN[$this->getGanIndex() + 1];
    }
    public function getZhi()
    {
        return LunarUtil::$ZHI[$this->getZhiIndex() + 1];
    }
    public function getGanZhi()
    {
        return $this->getGan() . $this->getZhi();
    }
    public function getPositionXi()
    {
        return LunarUtil::$POSITION_XI[$this->getGanIndex() + 1];
    }
    public function getPositionXiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionXi()];
    }
    public function getPositionYangGui()
    {
        return LunarUtil::$POSITION_YANG_GUI[$this->getGanIndex() + 1];
    }
    public function getPositionYangGuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionYangGui()];
    }
    public function getPositionYinGui()
    {
        return LunarUtil::$POSITION_YIN_GUI[$this->getGanIndex() + 1];
    }
    public function getPositionYinGuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionYinGui()];
    }
    public function getPositionFu($sect = 2)
    {
        $fu = 1 == $sect ? LunarUtil::$POSITION_FU : LunarUtil::$POSITION_FU_2;
        return $fu[$this->getGanIndex() + 1];
    }
    public function getPositionFuBySect($sect)
    {
        $fu = 1 == $sect ? LunarUtil::$POSITION_FU : LunarUtil::$POSITION_FU_2;
        return $fu[$this->getGanIndex() + 1];
    }
    public function getPositionFuDesc($sect = 2)
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionFu($sect)];
    }
    public function getPositionFuDescBySect($sect)
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionFu($sect)];
    }
    public function getPositionCai()
    {
        return LunarUtil::$POSITION_CAI[$this->getGanIndex() + 1];
    }
    public function getPositionCaiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionCai()];
    }
    public function isLeap()
    {
        return $this->month < 0;
    }
    public function getPositionTaiSui()
    {
        $m = abs($this->month);
        switch ($m) {
            case 1:
            case 5:
            case 9:
                $p = '艮';
                break;
            case 3:
            case 7:
            case 11:
                $p = '坤';
                break;
            case 4:
            case 8:
            case 12:
                $p = '巽';
                break;
            default:
                $p = LunarUtil::$POSITION_GAN[Solar::fromJulianDay($this->getFirstJulianDay())->getLunar()->getMonthGanIndex()];
        }
        return $p;
    }
    public function getPositionTaiSuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionTaiSui()];
    }
    public function getNineStar()
    {
        $index = LunarYear::fromYear($this->year)->getZhiIndex() % 3;
        $m = abs($this->month);
        $monthZhiIndex = (13 + $m) % 12;
        $n = 27 - ($index * 3);
        if ($monthZhiIndex < LunarUtil::$BASE_MONTH_ZHI_INDEX) {
            $n -= 3;
        }
        $offset = ($n - $monthZhiIndex) % 9;
        return NineStar::fromIndex($offset);
    }
    public function next($n)
    {
        if (0 == $n) {
            return LunarMonth::fromYm($this->year, $this->month);
        } else {
            $rest = abs($n);
            $ny = $this->year;
            $iy = $ny;
            $im = $this->month;
            $index = 0;
            $months = LunarYear::fromYear($ny)->getMonths();
            if ($n > 0) {
                while (true) {
                    $size = count($months);
                    for ($i = 0; $i < $size; $i++) {
                        $m = $months[$i];
                        if ($m->getYear() == $iy && $m->getMonth() == $im) {
                            $index = $i;
                            break;
                        }
                    }
                    $more = $size - $index - 1;
                    if ($rest < $more) {
                        break;
                    }
                    $rest -= $more;
                    $lastMonth = $months[$size - 1];
                    $iy = $lastMonth->getYear();
                    $im = $lastMonth->getMonth();
                    $ny++;
                    $months = LunarYear::fromYear($ny)->getMonths();
                }
                return $months[$index + $rest];
            } else {
                while (true) {
                    $size = count($months);
                    for ($i = 0; $i < $size; $i++) {
                        $m = $months[$i];
                        if ($m->getYear() == $iy && $m->getMonth() == $im) {
                            $index = $i;
                            break;
                        }
                    }
                    if ($rest <= $index) {
                        break;
                    }
                    $rest -= $index;
                    $firstMonth = $months[0];
                    $iy = $firstMonth->getYear();
                    $im = $firstMonth->getMonth();
                    $ny--;
                    $months = LunarYear::fromYear($ny)->getMonths();
                }
                return $months[$index - $rest];
            }
        }
    }
}
class LunarYear
{
    public static $YUAN = array('下', '上', '中');
    public static $YUN = array('七', '八', '九', '一', '二', '三', '四', '五', '六');
    private static $LEAP_11 = array(75, 94, 170, 265, 322, 398, 469, 553, 583, 610, 678, 735, 754, 773, 849, 887, 936, 1050, 1069, 1126, 1145, 1164, 1183, 1259, 1278, 1308, 1373, 1403, 1441, 1460, 1498, 1555, 1593, 1612, 1631, 1642, 2033, 2128, 2147, 2242, 2614, 2728, 2910, 3062, 3244, 3339, 3616, 3711, 3730, 3825, 4007, 4159, 4197, 4322, 4341, 4379, 4417, 4531, 4599, 4694, 4713, 4789, 4808, 4971, 5085, 5104, 5161, 5180, 5199, 5294, 5305, 5476, 5677, 5696, 5772, 5791, 5848, 5886, 6049, 6068, 6144, 6163, 6258, 6402, 6440, 6497, 6516, 6630, 6641, 6660, 6679, 6736, 6774, 6850, 6869, 6899, 6918, 6994, 7013, 7032, 7051, 7070, 7089, 7108, 7127, 7146, 7222, 7271, 7290, 7309, 7366, 7385, 7404, 7442, 7461, 7480, 7491, 7499, 7594, 7624, 7643, 7662, 7681, 7719, 7738, 7814, 7863, 7882, 7901, 7939, 7958, 7977, 7996, 8034, 8053, 8072, 8091, 8121, 8159, 8186, 8216, 8235, 8254, 8273, 8311, 8330, 8341, 8349, 8368, 8444, 8463, 8474, 8493, 8531, 8569, 8588, 8626, 8664, 8683, 8694, 8702, 8713, 8721, 8751, 8789, 8808, 8816, 8827, 8846, 8884, 8903, 8922, 8941, 8971, 9036, 9066, 9085, 9104, 9123, 9142, 9161, 9180, 9199, 9218, 9256, 9294, 9313, 9324, 9343, 9362, 9381, 9419, 9438, 9476, 9514, 9533, 9544, 9552, 9563, 9571, 9582, 9601, 9639, 9658, 9666, 9677, 9696, 9734, 9753, 9772, 9791, 9802, 9821, 9886, 9897, 9916, 9935, 9954, 9973, 9992);
    private static $LEAP_12 = array(37, 56, 113, 132, 151, 189, 208, 227, 246, 284, 303, 341, 360, 379, 417, 436, 458, 477, 496, 515, 534, 572, 591, 629, 648, 667, 697, 716, 792, 811, 830, 868, 906, 925, 944, 963, 982, 1001, 1020, 1039, 1058, 1088, 1153, 1202, 1221, 1240, 1297, 1335, 1392, 1411, 1422, 1430, 1517, 1525, 1536, 1574, 3358, 3472, 3806, 3988, 4751, 4941, 5066, 5123, 5275, 5343, 5438, 5457, 5495, 5533, 5552, 5715, 5810, 5829, 5905, 5924, 6421, 6535, 6793, 6812, 6888, 6907, 7002, 7184, 7260, 7279, 7374, 7556, 7746, 7757, 7776, 7833, 7852, 7871, 7966, 8015, 8110, 8129, 8148, 8224, 8243, 8338, 8406, 8425, 8482, 8501, 8520, 8558, 8596, 8607, 8615, 8645, 8740, 8778, 8835, 8865, 8930, 8960, 8979, 8998, 9017, 9055, 9074, 9093, 9112, 9150, 9188, 9237, 9275, 9332, 9351, 9370, 9408, 9427, 9446, 9457, 9465, 9495, 9560, 9590, 9628, 9647, 9685, 9715, 9742, 9780, 9810, 9818, 9829, 9848, 9867, 9905, 9924, 9943, 9962, 10000);
    private static $CACHE_YEAR;
    public static $YMC = array(11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
    private $year;
    private $ganIndex;
    private $zhiIndex;
    private $months = array();
    private $jieQiJulianDays = array();
    function __construct($lunarYear)
    {
        $lunarYear = intval($lunarYear);
        $this->year = $lunarYear;
        $offset = $lunarYear - 4;
        $yearGanIndex = $offset % 10;
        $yearZhiIndex = $offset % 12;
        if ($yearGanIndex < 0) {
            $yearGanIndex += 10;
        }
        if ($yearZhiIndex < 0) {
            $yearZhiIndex += 12;
        }
        $this->ganIndex = $yearGanIndex;
        $this->zhiIndex = $yearZhiIndex;
        $this->compute();
    }
    public static function fromYear($lunarYear)
    {
        if (LunarYear::$CACHE_YEAR == null || LunarYear::$CACHE_YEAR->getYear() != $lunarYear) {
            $y = new LunarYear($lunarYear);
            LunarYear::$CACHE_YEAR = $y;
        } else {
            $y = LunarYear::$CACHE_YEAR;
        }
        return $y;
    }
    public function toString()
    {
        return $this->year . '';
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        return $this->year . '年';
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getGanIndex()
    {
        return $this->ganIndex;
    }
    public function getZhiIndex()
    {
        return $this->zhiIndex;
    }
    public function getGan()
    {
        return LunarUtil::$GAN[$this->ganIndex + 1];
    }
    public function getZhi()
    {
        return LunarUtil::$ZHI[$this->zhiIndex + 1];
    }
    public function getGanZhi()
    {
        return $this->getGan() . $this->getZhi();
    }
    public function getJieQiJulianDays()
    {
        return $this->jieQiJulianDays;
    }
    public function getMonths()
    {
        return $this->months;
    }
    public function getDayCount()
    {
        $n = 0;
        foreach ($this->months as $m) {
            if ($m->getYear() == $this->year) {
                $n += $m->getDayCount();
            }
        }
        return $n;
    }
    public function getMonthsInYear()
    {
        $l = array();
        foreach ($this->months as $m) {
            if ($m->getYear() == $this->year) {
                $l[] = $m;
            }
        }
        return $l;
    }
    public function getMonth($lunarMonth)
    {
        foreach ($this->months as $m) {
            if ($m->getYear() == $this->year && $m->getMonth() == $lunarMonth) {
                return $m;
            }
        }
        return null;
    }
    public function getLeapMonth()
    {
        foreach ($this->months as $m) {
            if ($m->getYear() == $this->year && $m->isLeap()) {
                return abs($m->getMonth());
            }
        }
        return 0;
    }
    private function compute()
    {
        $jq = array();
        $hs = array();
        $dayCounts = array();
        $months = array();
        $currentYear = $this->year;
        $jd = floor(($currentYear - 2000) * 365.2422 + 180);
        $w = floor(($jd - 355 + 183) / 365.2422) * 365.2422 + 355;
        if (ShouXingUtil::calcQi($w) > $jd) {
            $w -= 365.2422;
        }
        for ($i = 0; $i < 26; $i++) {
            $jq[] = ShouXingUtil::calcQi($w + 15.2184 * $i);
        }
        for ($i = 0, $j = count(Lunar::$JIE_QI_IN_USE); $i < $j; $i++) {
            if ($i == 0) {
                $jd = ShouXingUtil::qiAccurate2($jq[0] - 15.2184);
            } else if ($i <= 26) {
                $jd = ShouXingUtil::qiAccurate2($jq[$i - 1]);
            } else {
                $jd = ShouXingUtil::qiAccurate2($jq[25] + 15.2184 * ($i - 26));
            }
            $this->jieQiJulianDays[] = $jd + Solar::$J2000;
        }
        $w = ShouXingUtil::calcShuo($jq[0]);
        if ($w > $jq[0]) {
            $w -= 29.53;
        }
        for ($i = 0; $i < 16; $i++) {
            $hs[] = ShouXingUtil::calcShuo($w + 29.5306 * $i);
        }
        for ($i = 0; $i < 15; $i++) {
            $dayCounts[] = intval($hs[$i + 1] - $hs[$i]);
            $months[] = $i;
        }
        $prevYear = $currentYear - 1;
        $leapIndex = 16;
        if (in_array($currentYear, self::$LEAP_11)) {
            $leapIndex = 13;
        } else if (in_array($currentYear, self::$LEAP_12)) {
            $leapIndex = 14;
        } else if ($hs[13] <= $jq[24]) {
            $i = 1;
            while ($hs[$i + 1] > $jq[2 * $i] && $i < 13) {
                $i++;
            }
            $leapIndex = $i;
        }
        for ($i = $leapIndex; $i < 15; $i++) {
            $months[$i] -= 1;
        }
        $fm = -1;
        $index = -1;
        $y = $prevYear;
        for ($i = 0; $i < 15; $i++) {
            $dm = $hs[$i] + Solar::$J2000;
            $v2 = $months[$i];
            $mc = self::$YMC[$v2 % 12];
            if (1724360 <= $dm && $dm < 1729794) {
                $mc = self::$YMC[($v2 + 1) % 12];
            } else if (1807724 <= $dm && $dm < 1808699) {
                $mc = self::$YMC[($v2 + 1) % 12];
            } else if ($dm == 1729794 || $dm == 1808699) {
                $mc = 12;
            }
            if ($fm == -1) {
                $fm = $mc;
                $index = $mc;
            }
            if ($mc < $fm) {
                $y += 1;
                $index = 1;
            }
            $fm = $mc;
            if ($i == $leapIndex) {
                $mc = -$mc;
            } else if ($dm == 1729794 || $dm == 1808699) {
                $mc = -11;
            }
            $this->months[] = new LunarMonth($y, $mc, $dayCounts[$i], $hs[$i] + Solar::$J2000, $index);
            $index++;
        }
    }
    protected function getZaoByGan($index, $name)
    {
        $month = $this->getMonth(1);
        if (null == $month) {
            return '';
        }
        $offset = $index - Solar::fromJulianDay($month->getFirstJulianDay())->getLunar()->getDayGanIndex();
        if ($offset < 0) {
            $offset += 10;
        }
        return preg_replace('/几/', LunarUtil::$NUMBER[$offset + 1], $name, 1);
    }
    protected function getZaoByZhi($index, $name)
    {
        $month = $this->getMonth(1);
        if (null == $month) {
            return '';
        }
        $offset = $index - Solar::fromJulianDay($month->getFirstJulianDay())->getLunar()->getDayZhiIndex();
        if ($offset < 0) {
            $offset += 12;
        }
        return preg_replace('/几/', LunarUtil::$NUMBER[$offset + 1], $name, 1);
    }
    public function getTouLiang()
    {
        return $this->getZaoByZhi(0, '几鼠偷粮');
    }
    public function getCaoZi()
    {
        return $this->getZaoByZhi(0, '草子几分');
    }
    public function getGengTian()
    {
        return $this->getZaoByZhi(1, '几牛耕田');
    }
    public function getHuaShou()
    {
        return $this->getZaoByZhi(3, '花收几分');
    }
    public function getZhiShui()
    {
        return $this->getZaoByZhi(4, '几龙治水');
    }
    public function getTuoGu()
    {
        return $this->getZaoByZhi(6, '几马驮谷');
    }
    public function getQiangMi()
    {
        return $this->getZaoByZhi(9, '几鸡抢米');
    }
    public function getKanCan()
    {
        return $this->getZaoByZhi(9, '几姑看蚕');
    }
    public function getGongZhu()
    {
        return $this->getZaoByZhi(11, '几屠共猪');
    }
    public function getJiaTian()
    {
        return $this->getZaoByGan(0, '甲田几分');
    }
    public function getFenBing()
    {
        return $this->getZaoByGan(2, '几人分饼');
    }
    public function getDeJin()
    {
        return $this->getZaoByGan(7, '几日得金');
    }
    public function getRenBing()
    {
        return $this->getZaoByGan(2, $this->getZaoByZhi(2, '几人几丙'));
    }
    public function getRenChu()
    {
        return $this->getZaoByGan(3, $this->getZaoByZhi(2, '几人几锄'));
    }
    public function getYuan()
    {
        return LunarYear::$YUAN[(int)(($this->year + 2696) / 60) % 3] . '元';
    }
    public function getYun()
    {
        return LunarYear::$YUN[(int)(($this->year + 2696) / 20) % 9] . '运';
    }
    public function getNineStar()
    {
        $index = LunarUtil::getJiaZiIndex($this->getGanZhi()) + 1;
        $yuan = intval(($this->year + 2696) / 60) % 3;
        $offset = (62 + $yuan * 3 - $index) % 9;
        if (0 === $offset) {
            $offset = 9;
        }
        return NineStar::fromIndex($offset - 1);
    }
    public function getPositionXi()
    {
        return LunarUtil::$POSITION_XI[$this->ganIndex + 1];
    }
    public function getPositionXiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionXi()];
    }
    public function getPositionYangGui()
    {
        return LunarUtil::$POSITION_YANG_GUI[$this->ganIndex + 1];
    }
    public function getPositionYangGuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionYangGui()];
    }
    public function getPositionYinGui()
    {
        return LunarUtil::$POSITION_YIN_GUI[$this->ganIndex + 1];
    }
    public function getPositionYinGuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionYinGui()];
    }
    public function getPositionFu($sect = 2)
    {
        $fu = 1 == $sect ? LunarUtil::$POSITION_FU : LunarUtil::$POSITION_FU_2;
        return $fu[$this->ganIndex + 1];
    }
    public function getPositionFuBySect($sect)
    {
        $fu = 1 == $sect ? LunarUtil::$POSITION_FU : LunarUtil::$POSITION_FU_2;
        return $fu[$this->ganIndex + 1];
    }
    public function getPositionFuDesc($sect = 2)
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionFu($sect)];
    }
    public function getPositionFuDescBySect($sect)
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionFu($sect)];
    }
    public function getPositionCai()
    {
        return LunarUtil::$POSITION_CAI[$this->ganIndex + 1];
    }
    public function getPositionCaiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionCai()];
    }
    public function getPositionTaiSui()
    {
        return LunarUtil::$POSITION_TAI_SUI_YEAR[$this->zhiIndex];
    }
    public function getPositionTaiSuiDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPositionTaiSui()];
    }
    public function next($n)
    {
        return LunarYear::fromYear($this->year + $n);
    }
}
class NineStar
{
    private $index;
    public static $NUMBER = array('一', '二', '三', '四', '五', '六', '七', '八', '九');
    public static $COLOR = array('白', '黑', '碧', '绿', '黄', '白', '赤', '白', '紫');
    public static $WU_XING = array('水', '土', '木', '木', '土', '金', '金', '土', '火');
    public static $POSITION = array('坎', '坤', '震', '巽', '中', '乾', '兑', '艮', '离');
    public static $NAME_BEI_DOU = array('天枢', '天璇', '天玑', '天权', '玉衡', '开阳', '摇光', '洞明', '隐元');
    public static $NAME_XUAN_KONG = array('贪狼', '巨门', '禄存', '文曲', '廉贞', '武曲', '破军', '左辅', '右弼');
    public static $NAME_QI_MEN = array('天蓬', '天芮', '天冲', '天辅', '天禽', '天心', '天柱', '天任', '天英');
    public static $BA_MEN_QI_MEN = array('休', '死', '伤', '杜', '', '开', '惊', '生', '景');
    public static $NAME_TAI_YI = array('太乙', '摄提', '轩辕', '招摇', '天符', '青龙', '咸池', '太阴', '天乙');
    public static $TYPE_TAI_YI = array('吉神', '凶神', '安神', '安神', '凶神', '吉神', '凶神', '吉神', '吉神');
    public static $SONG_TAI_YI = array('门中太乙明，星官号贪狼，赌彩财喜旺，婚姻大吉昌，出入无阻挡，参谒见贤良，此行三五里，黑衣别阴阳。', '门前见摄提，百事必忧疑，相生犹自可，相克祸必临，死门并相会，老妇哭悲啼，求谋并吉事，尽皆不相宜，只可藏隐遁，若动伤身疾。', '出入会轩辕，凡事必缠牵，相生全不美，相克更忧煎，远行多不利，博彩尽输钱，九天玄女法，句句不虚言。', '招摇号木星，当之事莫行，相克行人阻，阴人口舌迎，梦寐多惊惧，屋响斧自鸣，阴阳消息理，万法弗违情。', '五鬼为天符，当门阴女谋，相克无好事，行路阻中途，走失难寻觅，道逢有尼姑，此星当门值，万事有灾除。', '神光跃青龙，财气喜重重，投入有酒食，赌彩最兴隆，更逢相生旺，休言克破凶，见贵安营寨，万事总吉同。', '吾将为咸池，当之尽不宜，出入多不利，相克有灾情，赌彩全输尽，求财空手回，仙人真妙语，愚人莫与知，动用虚惊退，反复逆风吹。', '坐临太阴星，百祸不相侵，求谋悉成就，知交有觅寻，回风归来路，恐有殃伏起，密语中记取，慎乎莫轻行。', '迎来天乙星，相逢百事兴，运用和合庆，茶酒喜相迎，求谋并嫁娶，好合有天成，祸福如神验，吉凶甚分明。');
    public static $LUCK_XUAN_KONG = array('吉', '凶', '凶', '吉', '凶', '吉', '凶', '吉', '吉');
    public static $LUCK_QI_MEN = array('大凶', '大凶', '小吉', '大吉', '大吉', '大吉', '小凶', '小吉', '小凶');
    public static $YIN_YANG_QI_MEN = array('阳', '阴', '阳', '阳', '阳', '阴', '阴', '阳', '阴');
    function __construct($index)
    {
        $this->index = $index;
    }
    public static function fromIndex($index)
    {
        return new NineStar($index);
    }
    public function getNumber()
    {
        return NineStar::$NUMBER[$this->index];
    }
    public function getColor()
    {
        return NineStar::$COLOR[$this->index];
    }
    public function getWuXing()
    {
        return NineStar::$WU_XING[$this->index];
    }
    public function getPosition()
    {
        return NineStar::$POSITION[$this->index];
    }
    public function getPositionDesc()
    {
        return LunarUtil::$POSITION_DESC[$this->getPosition()];
    }
    public function getNameInXuanKong()
    {
        return NineStar::$NAME_XUAN_KONG[$this->index];
    }
    public function getNameInBeiDou()
    {
        return NineStar::$NAME_BEI_DOU[$this->index];
    }
    public function getNameInQiMen()
    {
        return NineStar::$NAME_QI_MEN[$this->index];
    }
    public function getNameInTaiYi()
    {
        return NineStar::$NAME_TAI_YI[$this->index];
    }
    public function getLuckInQiMen()
    {
        return NineStar::$LUCK_QI_MEN[$this->index];
    }
    public function getLuckInXuanKong()
    {
        return NineStar::$LUCK_XUAN_KONG[$this->index];
    }
    public function getYinYangInQiMen()
    {
        return NineStar::$YIN_YANG_QI_MEN[$this->index];
    }
    public function getTypeInTaiYi()
    {
        return NineStar::$TYPE_TAI_YI[$this->index];
    }
    public function getBaMenInQiMen()
    {
        return NineStar::$BA_MEN_QI_MEN[$this->index];
    }
    public function getSongInTaiYi()
    {
        return NineStar::$SONG_TAI_YI[$this->index];
    }
    public function getIndex()
    {
        return $this->index;
    }
    public function toString()
    {
        return $this->getNumber() . $this->getColor() . $this->getWuXing() . $this->getNameInBeiDou();
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        $s = $this->getNumber();
        $s .= $this->getColor();
        $s .= $this->getWuXing();
        $s .= ' ';
        $s .= $this->getPosition();
        $s .= '(';
        $s .= $this->getPositionDesc();
        $s .= ') ';
        $s .= $this->getNameInBeiDou();
        $s .= ' 玄空[';
        $s .= $this->getNameInXuanKong();
        $s .= ' ';
        $s .= $this->getLuckInXuanKong();
        $s .= '] 奇门[';
        $s .= $this->getNameInQiMen();
        $s .= ' ';
        $s .= $this->getLuckInQiMen();
        if (strlen($this->getBaMenInQiMen()) > 0) {
            $s .= ' ';
            $s .= $this->getBaMenInQiMen();
            $s .= '门';
        }
        $s .= ' ';
        $s .= $this->getYinYangInQiMen();
        $s .= '] 太乙[';
        $s .= $this->getNameInTaiYi();
        $s .= ' ';
        $s .= $this->getTypeInTaiYi();
        $s .= ']';
        return $s;
    }
}
class ShuJiu
{
    private $name;
    private $index;
    function __construct($name, $index)
    {
        $this->name = $name;
        $this->index = $index;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
    }
    public function getIndex()
    {
        return $this->index;
    }
    public function setIndex($index)
    {
        $this->index = $index;
    }
    public function toString()
    {
        return $this->name;
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        return $this->name . '第' . $this->index . '天';
    }
}
class Solar
{
    public static $J2000 = 2451545;
    private $year;
    private $month;
    private $day;
    private $hour;
    private $minute;
    private $second;
    function __construct($year, $month, $day, $hour, $minute, $second)
    {
        $year = intval($year);
        $month = intval($month);
        $day = intval($day);
        $hour = intval($hour);
        $minute = intval($minute);
        $second = intval($second);
        if ($month < 1 || $month > 12) {
            throw new RuntimeException(sprintf('wrong month %d', $month));
        }
        if ($day < 1) {
            throw new RuntimeException(sprintf('wrong day %d', $day));
        }
        if (1582 == $year && 10 == $month) {
            if ($day > 4 && $day < 15) {
                throw new RuntimeException(sprintf('wrong solar year %d month %d day %d', $year, $month, $day));
            }
        } else {
            $days = SolarUtil::getDaysOfMonth($year, $month);
            if ($day > $days) {
                throw new RuntimeException(sprintf('only %d days in solar year %d month %d', $days, $year, $month));
            }
        }
        if ($hour < 0 || $hour > 23) {
            throw new RuntimeException(sprintf('wrong hour %d', $hour));
        }
        if ($minute < 0 || $minute > 59) {
            throw new RuntimeException(sprintf('wrong minute %d', $minute));
        }
        if ($second < 0 || $second > 59) {
            throw new RuntimeException(sprintf('wrong second %d', $second));
        }
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
    }
    public static function fromDate($date)
    {
        $calendar = DateTime::createFromFormat('Y-n-j G:i:s', $date->format('Y-n-j G:i:s'), $date->getTimezone());
        $calendar->setTimezone(new DateTimezone('Asia/Shanghai'));
        $year = intval($calendar->format('Y'));
        $month = intval($calendar->format('n'));
        $day = intval($calendar->format('j'));
        $hour = intval($calendar->format('G'));
        $minute = intval($calendar->format('i'));
        $second = intval($calendar->format('s'));
        return new Solar($year, $month, $day, $hour, $minute, $second);
    }
    public static function fromJulianDay($julianDay)
    {
        $d = (int)($julianDay + 0.5);
        $f = $julianDay + 0.5 - $d;
        if ($d >= 2299161) {
            $c = (int)(($d - 1867216.25) / 36524.25);
            $d += 1 + $c - (int)($c / 4);
        }
        $d += 1524;
        $year = (int)(($d - 122.1) / 365.25);
        $d -= (int)(365.25 * $year);
        $month = (int)($d / 30.601);
        $d -= (int)(30.601 * $month);
        $day = $d;
        if ($month > 13) {
            $month -= 13;
            $year -= 4715;
        } else {
            $month -= 1;
            $year -= 4716;
        }
        $f *= 24;
        $hour = (int)$f;
        $f -= $hour;
        $f *= 60;
        $minute = (int)$f;
        $f -= $minute;
        $f *= 60;
        $second = intval(round($f));
        if ($second > 59) {
            $second -= 60;
            $minute++;
        }
        if ($minute > 59) {
            $minute -= 60;
            $hour++;
        }
        if ($hour > 23) {
            $hour -= 24;
            $day++;
        }
        return self::fromYmdHms($year, $month, $day, $hour, $minute, $second);
    }
    public static function fromBaZi($yearGanZhi, $monthGanZhi, $dayGanZhi, $timeGanZhi, $sect = 2, $baseYear = 1900)
    {
        return self::_fromBaZiBySectAndBaseYear($yearGanZhi, $monthGanZhi, $dayGanZhi, $timeGanZhi, $sect, $baseYear);
    }
    public static function fromBaZiBySect($yearGanZhi, $monthGanZhi, $dayGanZhi, $timeGanZhi, $sect)
    {
        return self::_fromBaZiBySectAndBaseYear($yearGanZhi, $monthGanZhi, $dayGanZhi, $timeGanZhi, $sect, 1900);
    }
    public static function fromBaZiBySectAndBaseYear($yearGanZhi, $monthGanZhi, $dayGanZhi, $timeGanZhi, $sect, $baseYear)
    {
        return self::_fromBaZiBySectAndBaseYear($yearGanZhi, $monthGanZhi, $dayGanZhi, $timeGanZhi, $sect, $baseYear);
    }
    public static function _fromBaZiBySectAndBaseYear($yearGanZhi, $monthGanZhi, $dayGanZhi, $timeGanZhi, $sect, $baseYear)
    {
        $sect = (1 == $sect) ? 1 : 2;
        $l = array();
        $m = LunarUtil::index(substr($monthGanZhi, strlen($monthGanZhi) / 2), LunarUtil::$ZHI, -1) - 2;
        if ($m < 0) {
            $m += 12;
        }
        if (((LunarUtil::index(substr($yearGanZhi, 0, strlen($yearGanZhi) / 2), LunarUtil::$GAN, -1) + 1) * 2 + $m) % 10 != LunarUtil::index(substr($monthGanZhi, 0, strlen($monthGanZhi) / 2), LunarUtil::$GAN, -1)) {
            return $l;
        }
        $y = LunarUtil::getJiaZiIndex($yearGanZhi) - 57;
        if ($y < 0) {
            $y += 60;
        }
        $y++;
        $m *= 2;
        $h = LunarUtil::index(substr($timeGanZhi, strlen($timeGanZhi) / 2), LunarUtil::$ZHI, -1) * 2;
        $hours = array($h);
        if (0 == $h && 2 == $sect) {
            $hours = array(0, 23);
        }
        $startYear = $baseYear - 1;
        $date = new DateTime();
        $calendar = DateTime::createFromFormat('Y-n-j G:i:s', $date->format('Y-n-j G:i:s'), $date->getTimezone());
        $calendar->setTimezone(new DateTimezone('Asia/Shanghai'));
        $endYear = intval($calendar->format('Y'));
        while ($y <= $endYear) {
            if ($y >= $startYear) {
                $jieQiTable = Lunar::fromYmd($y, 1, 1)->getJieQiTable();
                $solarTime = $jieQiTable[Lunar::$JIE_QI_IN_USE[4 + $m]];
                if ($solarTime->getYear() >= $baseYear) {
                    $d = LunarUtil::getJiaZiIndex($dayGanZhi) - LunarUtil::getJiaZiIndex($solarTime->getLunar()->getDayInGanZhiExact2());
                    if ($d < 0) {
                        $d += 60;
                    }
                    if ($d > 0) {
                        $solarTime = $solarTime->next($d);
                    }
                    foreach ($hours as $hour) {
                        $mi = 0;
                        $s = 0;
                        if ($d == 0 && $hour == $solarTime->getHour()) {
                            $mi = $solarTime->getMinute();
                            $s = $solarTime->getSecond();
                        }
                        $solar = Solar::fromYmdHms($solarTime->getYear(), $solarTime->getMonth(), $solarTime->getDay(), $hour, $mi, $s);
                        $lunar = $solar->getLunar();
                        $dgz = (2 == $sect) ? $lunar->getDayInGanZhiExact2() : $lunar->getDayInGanZhiExact();
                        if (strcmp($lunar->getYearInGanZhiExact(), $yearGanZhi) == 0 && strcmp($lunar->getMonthInGanZhiExact(), $monthGanZhi) == 0 && strcmp($dgz, $dayGanZhi) == 0 && strcmp($lunar->getTimeInGanZhi(), $timeGanZhi) == 0) {
                            $l[] = $solar;
                        }
                    }
                }
            }
            $y += 60;
        }
        return $l;
    }
    public static function fromYmd($year, $month, $day)
    {
        return new Solar($year, $month, $day, 0, 0, 0);
    }
    public static function fromYmdHms($year, $month, $day, $hour, $minute, $second)
    {
        return new Solar($year, $month, $day, $hour, $minute, $second);
    }
    public function toYmd()
    {
        return sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day);
    }
    public function toYmdHms()
    {
        return $this->toYmd() . ' ' . sprintf('%02d:%02d:%02d', $this->hour, $this->minute, $this->second);
    }
    public function toFullString()
    {
        $s = $this->toYmdHms();
        if ($this->isLeapYear()) {
            $s .= ' 闰年';
        }
        $s .= ' 星期' . $this->getWeekInChinese();
        foreach ($this->getFestivals() as $f) {
            $s .= ' (' . $f . ')';
        }
        $s .= ' ' . $this->getXingZuo() . '座';
        return $s;
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getMonth()
    {
        return $this->month;
    }
    public function getDay()
    {
        return $this->day;
    }
    public function getHour()
    {
        return $this->hour;
    }
    public function getMinute()
    {
        return $this->minute;
    }
    public function getSecond()
    {
        return $this->second;
    }
    public function getJulianDay()
    {
        $y = $this->year;
        $m = $this->month;
        $d = $this->day + (($this->second / 60 + $this->minute) / 60 + $this->hour) / 24;
        $n = 0;
        $g = false;
        if ($y * 372 + $m * 31 + (int)$d >= 588829) {
            $g = true;
        }
        if ($m <= 2) {
            $m += 12;
            $y--;
        }
        if ($g) {
            $n = (int)($y / 100);
            $n = 2 - $n + (int)($n / 4);
        }
        return (int)(365.25 * ($y + 4716)) + (int)(30.6001 * ($m + 1)) + $d + $n - 1524.5;
    }
    public function getLunar()
    {
        return Lunar::fromSolar($this);
    }
    public function toString()
    {
        return $this->toYmd();
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function isLeapYear()
    {
        return SolarUtil::isLeapYear($this->year);
    }
    public function getWeekInChinese()
    {
        return SolarUtil::$WEEK[$this->getWeek()];
    }
    public function getXingZuo()
    {
        $index = 11;
        $y = $this->month * 100 + $this->day;
        if ($y >= 321 && $y <= 419) {
            $index = 0;
        } else if ($y >= 420 && $y <= 520) {
            $index = 1;
        } else if ($y >= 521 && $y <= 621) {
            $index = 2;
        } else if ($y >= 622 && $y <= 722) {
            $index = 3;
        } else if ($y >= 723 && $y <= 822) {
            $index = 4;
        } else if ($y >= 823 && $y <= 922) {
            $index = 5;
        } else if ($y >= 923 && $y <= 1023) {
            $index = 6;
        } else if ($y >= 1024 && $y <= 1122) {
            $index = 7;
        } else if ($y >= 1123 && $y <= 1221) {
            $index = 8;
        } else if ($y >= 1222 || $y <= 119) {
            $index = 9;
        } else if ($y <= 218) {
            $index = 10;
        }
        return SolarUtil::$XING_ZUO[$index];
    }
    public function getFestivals()
    {
        $l = array();
        $key = $this->month . '-' . $this->day;
        if (!empty(SolarUtil::$FESTIVAL[$key])) {
            $l[] = SolarUtil::$FESTIVAL[$key];
        }
        $weeks = intval(ceil($this->day / 7.0));
        $week = $this->getWeek();
        $key = $this->month . '-' . $weeks . '-' . $week;
        if (!empty(SolarUtil::$WEEK_FESTIVAL[$key])) {
            $l[] = SolarUtil::$WEEK_FESTIVAL[$key];
        }
        if ($this->day + 7 > SolarUtil::getDaysOfMonth($this->year, $this->month)) {
            $key = $this->month . '-0-' . $week;
            if (!empty(SolarUtil::$WEEK_FESTIVAL[$key])) {
                $l[] = SolarUtil::$WEEK_FESTIVAL[$key];
            }
        }
        return $l;
    }
    public function getOtherFestivals()
    {
        $l = array();
        $key = $this->month . '-' . $this->day;
        if (!empty(SolarUtil::$OTHER_FESTIVAL[$key])) {
            foreach (SolarUtil::$OTHER_FESTIVAL[$key] as $f) {
                $l[] = $f;
            }
        }
        return $l;
    }
    public function subtract($solar)
    {
        return SolarUtil::getDaysBetween($solar->getYear(), $solar->getMonth(), $solar->getDay(), $this->getYear(), $this->getMonth(), $this->getDay());
    }
    public function subtractMinute($solar)
    {
        $days = $this->subtract($solar);
        $cm = $this->getHour() * 60 + $this->getMinute();
        $sm = $solar->getHour() * 60 + $solar->getMinute();
        $m = $cm - $sm;
        if ($m < 0) {
            $m += 1440;
            $days--;
        }
        $m += $days * 1440;
        return $m;
    }
    public function isAfter($solar)
    {
        if ($this->year > $solar->getYear()) {
            return true;
        }
        if ($this->year < $solar->getYear()) {
            return false;
        }
        if ($this->month > $solar->getMonth()) {
            return true;
        }
        if ($this->month < $solar->getMonth()) {
            return false;
        }
        if ($this->day > $solar->getDay()) {
            return true;
        }
        if ($this->day < $solar->getDay()) {
            return false;
        }
        if ($this->hour > $solar->getHour()) {
            return true;
        }
        if ($this->hour < $solar->getHour()) {
            return false;
        }
        if ($this->minute > $solar->getMinute()) {
            return true;
        }
        if ($this->minute < $solar->getMinute()) {
            return false;
        }
        return $this->second > $solar->second;
    }
    public function isBefore($solar)
    {
        if ($this->year > $solar->getYear()) {
            return false;
        }
        if ($this->year < $solar->getYear()) {
            return true;
        }
        if ($this->month > $solar->getMonth()) {
            return false;
        }
        if ($this->month < $solar->getMonth()) {
            return true;
        }
        if ($this->day > $solar->getDay()) {
            return false;
        }
        if ($this->day < $solar->getDay()) {
            return true;
        }
        if ($this->hour > $solar->getHour()) {
            return false;
        }
        if ($this->hour < $solar->getHour()) {
            return true;
        }
        if ($this->minute > $solar->getMinute()) {
            return false;
        }
        if ($this->minute < $solar->getMinute()) {
            return true;
        }
        return $this->second < $solar->second;
    }
    public function nextYear($years)
    {
        $y = $this->year + $years;
        $m = $this->month;
        $d = $this->day;
        if (1582 == $y && 10 == $m) {
            if ($d > 4 && $d < 15) {
                $d += 10;
            }
        } else if (2 == $m) {
            if ($d > 28) {
                if (!SolarUtil::isLeapYear($y)) {
                    $d = 28;
                }
            }
        }
        return self::fromYmdHms($y, $m, $d, $this->hour, $this->minute, $this->second);
    }
    public function nextMonth($months)
    {
        $month = SolarMonth::fromYm($this->year, $this->month)->next($months);
        $y = $month->getYear();
        $m = $month->getMonth();
        $d = $this->day;
        if (1582 == $y && 10 == $m) {
            if ($d > 4 && $d < 15) {
                $d += 10;
            }
        } else {
            $days = SolarUtil::getDaysOfMonth($y, $m);
            if ($d > $days) {
                $d = $days;
            }
        }
        return self::fromYmdHms($y, $m, $d, $this->hour, $this->minute, $this->second);
    }
    public function next($days)
    {
        $y = $this->year;
        $m = $this->month;
        $d = $this->day;
        if (1582 == $y && 10 == $m) {
            if ($d > 4) {
                $d -= 10;
            }
        }
        if ($days > 0) {
            $d += $days;
            $daysInMonth = SolarUtil::getDaysOfMonth($y, $m);
            while ($d > $daysInMonth) {
                $d -= $daysInMonth;
                $m++;
                if ($m > 12) {
                    $m = 1;
                    $y++;
                }
                $daysInMonth = SolarUtil::getDaysOfMonth($y, $m);
            }
        } else if ($days < 0) {
            while ($d + $days <= 0) {
                $m--;
                if ($m < 1) {
                    $m = 12;
                    $y--;
                }
                $d += SolarUtil::getDaysOfMonth($y, $m);
            }
            $d += $days;
        }
        if (1582 == $y && 10 == $m) {
            if ($d > 4) {
                $d += 10;
            }
        }
        return self::fromYmdHms($y, $m, $d, $this->hour, $this->minute, $this->second);
    }
    public function nextHour($hours)
    {
        $h = $this->hour + $hours;
        $n = $h < 0 ? -1 : 1;
        $hour = (int)abs($h);
        $days = (int)($hour / 24) * $n;
        $hour = ($hour % 24) * $n;
        if ($hour < 0) {
            $hour += 24;
            $days--;
        }
        $solar = $this->next($days);
        return self::fromYmdHms($solar->getYear(), $solar->getMonth(), $solar->getDay(), $hour, $solar->getMinute(), $solar->getSecond());
    }
    public function getWeek()
    {
        return ((int)($this->getJulianDay() + 0.5) + 7000001) % 7;
    }
    public function nextWorkday($days)
    {
        $solar = self::fromYmdHms($this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second);
        if ($days != 0) {
            $rest = abs($days);
            $add = $days < 0 ? -1 : 1;
            while ($rest > 0) {
                $solar = $solar->next($add);
                $work = true;
                $holiday = HolidayUtil::getHolidayByYmd($solar->getYear(), $solar->getMonth(), $solar->getDay());
                if (null == $holiday) {
                    $week = $solar->getWeek();
                    if (0 === $week || 6 === $week) {
                        $work = false;
                    }
                } else {
                    $work = $holiday->isWork();
                }
                if ($work) {
                    $rest -= 1;
                }
            }
        }
        return $solar;
    }
    public function getSalaryRate()
    {
        if ($this->month == 1 && $this->day == 1) {
            return 3;
        }
        if ($this->month == 5 && $this->day == 1) {
            return 3;
        }
        if ($this->month == 10 && $this->day >= 1 && $this->day <= 3) {
            return 3;
        }
        $lunar = $this->getLunar();
        if ($lunar->getMonth() == 1 && $lunar->getDay() >= 1 && $lunar->getDay() <= 3) {
            return 3;
        }
        if ($lunar->getMonth() == 5 && $lunar->getDay() == 5) {
            return 3;
        }
        if ($lunar->getMonth() == 8 && $lunar->getDay() == 15) {
            return 3;
        }
        if (strcmp('清明', $lunar->getJieQi()) === 0) {
            return 3;
        }
        $holiday = HolidayUtil::getHolidayByYmd($this->year, $this->month, $this->day);
        if (null != $holiday) {
            if (!$holiday->isWork()) {
                return 2;
            }
        } else {
            $week = $this->getWeek();
            if ($week == 6 || $week == 0) {
                return 2;
            }
        }
        return 1;
    }
}
class SolarHalfYear
{
    private $year;
    private $month;
    public static $MONTH_COUNT = 6;
    function __construct($year, $month)
    {
        $this->year = intval($year);
        $this->month = intval($month);
    }
    public function toString()
    {
        return $this->year . '.' . $this->getIndex();
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        return $this->year . '年' . (1 === $this->getIndex() ? '上' : '下') . '半年';
    }
    public static function fromYm($year, $month)
    {
        return new SolarHalfYear($year, $month);
    }
    public static function fromDate($date)
    {
        $solar = Solar::fromDate($date);
        return new SolarHalfYear($solar->getYear(), $solar->getMonth());
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getMonth()
    {
        return $this->month;
    }
    public function getIndex()
    {
        return (int)ceil($this->month / SolarHalfYear::$MONTH_COUNT);
    }
    public function getMonths()
    {
        $l = array();
        $index = $this->getIndex() - 1;
        for ($i = 0; $i < SolarHalfYear::$MONTH_COUNT; $i++) {
            $l[] = new SolarMonth($this->year, SolarHalfYear::$MONTH_COUNT * $index + $i + 1);
        }
        return $l;
    }
    public function next($halfYears)
    {
        $month = SolarMonth::fromYm($this->year, $this->month)->next(self::$MONTH_COUNT * $halfYears);
        return new SolarHalfYear($month->getYear(), $month->getMonth());
    }
}
class SolarMonth
{
    private $year;
    private $month;
    function __construct($year, $month)
    {
        $this->year = intval($year);
        $this->month = intval($month);
    }
    public function toString()
    {
        return $this->year . '-' . $this->month;
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        return $this->year . '年' . $this->month . '月';
    }
    public static function fromYm($year, $month)
    {
        return new SolarMonth($year, $month);
    }
    public static function fromDate($date)
    {
        $solar = Solar::fromDate($date);
        return new SolarMonth($solar->getYear(), $solar->getMonth());
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getMonth()
    {
        return $this->month;
    }
    public function getDays()
    {
        $l = array();
        $d = Solar::fromYmd($this->year, $this->month, 1);
        $l[] = $d;
        $days = SolarUtil::getDaysOfMonth($this->year, $this->month);
        for ($i = 1; $i < $days; $i++) {
            $l[] = $d->next($i);
        }
        return $l;
    }
    public function getWeeks($start)
    {
        $l = array();
        $week = SolarWeek::fromYmd($this->year, $this->month, 1, $start);
        while (true) {
            $l[] = $week;
            $week = $week->next(1, false);
            $firstDay = $week->getFirstDay();
            if ($firstDay->getYear() > $this->year || $firstDay->getMonth() > $this->month) {
                break;
            }
        }
        return $l;
    }
    public function next($months)
    {
        $n = $months < 0 ? -1 : 1;
        $m = abs($months);
        $y = $this->year + (int)($m / 12) * $n;
        $m = $this->month + $m % 12 * $n;
        if ($m > 12) {
            $m -= 12;
            $y++;
        } else if ($m < 1) {
            $m += 12;
            $y--;
        }
        return new SolarMonth($y, $m);
    }
}
class SolarSeason
{
    private $year;
    private $month;
    public static $MONTH_COUNT = 3;
    function __construct($year, $month)
    {
        $this->year = intval($year);
        $this->month = intval($month);
    }
    public function toString()
    {
        return $this->year . '.' . $this->getIndex();
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        return $this->year . '年' . $this->getIndex() . '季度';
    }
    public static function fromYm($year, $month)
    {
        return new SolarSeason($year, $month);
    }
    public static function fromDate($date)
    {
        $solar = Solar::fromDate($date);
        return new SolarSeason($solar->getYear(), $solar->getMonth());
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getMonth()
    {
        return $this->month;
    }
    public function getIndex()
    {
        return (int)ceil($this->month / SolarSeason::$MONTH_COUNT);
    }
    public function getMonths()
    {
        $l = array();
        $index = $this->getIndex() - 1;
        for ($i = 0; $i < self::$MONTH_COUNT; $i++) {
            $l[] = new SolarMonth($this->year, self::$MONTH_COUNT * $index + $i + 1);
        }
        return $l;
    }
    public function next($seasons)
    {
        $month = SolarMonth::fromYm($this->year, $this->month);
        $month = $month->next(self::$MONTH_COUNT * $seasons);
        return new SolarSeason($month->getYear(), $month->getMonth());
    }
}
class SolarWeek
{
    private $year;
    private $month;
    private $day;
    private $start;
    function __construct($year, $month, $day, $start)
    {
        $this->year = intval($year);
        $this->month = intval($month);
        $this->day = intval($day);
        $this->start = intval($start);
    }
    public function toString()
    {
        return $this->year . '.' . $this->month . '.' . $this->getIndex();
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        return $this->year . '年' . $this->month . '月第' . $this->getIndex() . '周';
    }
    public static function fromYmd($year, $month, $day, $start)
    {
        return new SolarWeek($year, $month, $day, $start);
    }
    public static function fromDate($date, $start)
    {
        $solar = Solar::fromDate($date);
        return new SolarWeek($solar->getYear(), $solar->getMonth(), $solar->getDay(), $start);
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getMonth()
    {
        return $this->month;
    }
    public function getDay()
    {
        return $this->day;
    }
    public function getStart()
    {
        return $this->start;
    }
    public function getIndex()
    {
        $offset = Solar::fromYmd($this->year, $this->month, 1)->getWeek() - $this->start;
        if ($offset < 0) {
            $offset += 7;
        }
        return (int)ceil(($this->day + $offset) / 7);
    }
    public function getIndexInYear()
    {
        $offset = Solar::fromYmd($this->year, 1, 1)->getWeek() - $this->start;
        if ($offset < 0) {
            $offset += 7;
        }
        return (int)ceil((SolarUtil::getDaysInYear($this->year, $this->month, $this->day) + $offset) / 7);
    }
    public function next($weeks, $separateMonth)
    {
        if (0 === $weeks) {
            return SolarWeek::fromYmd($this->year, $this->month, $this->day, $this->start);
        }
        $solar = Solar::fromYmd($this->year, $this->month, $this->day);
        if ($separateMonth) {
            $n = $weeks;
            $week = SolarWeek::fromYmd($solar->getYear(), $solar->getMonth(), $solar->getDay(), $this->start);
            $month = $this->month;
            $plus = $n > 0;
            while (0 !== $n) {
                $solar = $solar->next($plus ? 7 : -7);
                $week = SolarWeek::fromYmd($solar->getYear(), $solar->getMonth(), $solar->getDay(), $this->start);
                $weekMonth = $week->getMonth();
                if ($month !== $weekMonth) {
                    $index = $week->getIndex();
                    if ($plus) {
                        if (1 === $index) {
                            $firstDay = $week->getFirstDay();
                            $week = SolarWeek::fromYmd($firstDay->getYear(), $firstDay->getMonth(), $firstDay->getDay(), $this->start);
                            $weekMonth = $week->getMonth();
                        } else {
                            $solar = Solar::fromYmd($week->getYear(), $week->getMonth(), 1);
                            $week = SolarWeek::fromYmd($solar->getYear(), $solar->getMonth(), $solar->getDay(), $this->start);
                        }
                    } else {
                        if (SolarUtil::getWeeksOfMonth($week->getYear(), $week->getMonth(), $week->getStart()) === $index) {
                            $lastDay = $week->getFirstDay()->next(6);
                            $week = SolarWeek::fromYmd($lastDay->getYear(), $lastDay->getMonth(), $lastDay->getDay(), $this->start);
                            $weekMonth = $week->getMonth();
                        } else {
                            $solar = Solar::fromYmd($week->year, $week->month, SolarUtil::getDaysOfMonth($week->getYear(), $week->getMonth()));
                            $week = SolarWeek::fromYmd($solar->getYear(), $solar->getMonth(), $solar->getDay(), $this->start);
                        }
                    }
                    $month = $weekMonth;
                }
                $n -= $plus ? 1 : -1;
            }
            return $week;
        } else {
            $solar = $solar->next($weeks * 7);
            return SolarWeek::fromYmd($solar->getYear(), $solar->getMonth(), $solar->getDay(), $this->start);
        }
    }
    public function getFirstDay()
    {
        $solar = Solar::fromYmd($this->year, $this->month, $this->day);
        $prev = $solar->getWeek() - $this->start;
        if ($prev < 0) {
            $prev += 7;
        }
        return $solar->next(-$prev);
    }
    public function getFirstDayInMonth()
    {
        $days = $this->getDays();
        foreach ($days as $day) {
            if ($this->month === $day->getMonth()) {
                return $day;
            }
        }
        return null;
    }
    public function getDays()
    {
        $firstDay = $this->getFirstDay();
        $l = array();
        if (null == $firstDay) {
            return $l;
        }
        $l[] = $firstDay;
        for ($i = 1; $i < 7; $i++) {
            $l[] = $firstDay->next($i);
        }
        return $l;
    }
    public function getDaysInMonth()
    {
        $days = $this->getDays();
        $l = array();
        foreach ($days as $day) {
            if ($this->month !== $day->getMonth()) {
                continue;
            }
            $l[] = $day;
        }
        return $l;
    }
}
class SolarYear
{
    private $year;
    public static $MONTH_COUNT = 12;
    function __construct($year)
    {
        $this->year = intval($year);
    }
    public function toString()
    {
        return $this->year . '';
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        return $this->year . '年';
    }
    public static function fromYear($year)
    {
        return new SolarYear($year);
    }
    public static function fromDate($date)
    {
        return new SolarYear(Solar::fromDate($date)->getYear());
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getMonths()
    {
        $l = array();
        $month = SolarMonth::fromYm($this->year, 1);
        $l[] = $month;
        for ($i = 1; $i < SolarYear::$MONTH_COUNT; $i++) {
            $l[] = $month->next($i);
        }
        return $l;
    }
    public function next($years)
    {
        return new SolarYear($this->year + $years);
    }
}
class XiaoYun
{
    private $index;
    private $daYun;
    private $year;
    private $age;
    private $lunar;
    private $forward;
    public function __construct(DaYun $daYun, $index, $forward)
    {
        $this->daYun = $daYun;
        $this->lunar = $daYun->getLunar();
        $this->index = $index;
        $this->year = $daYun->getStartYear() + $index;
        $this->age = $daYun->getStartAge() + $index;
        $this->forward = $forward;
    }
    public function getIndex()
    {
        return $this->index;
    }
    public function getDaYun()
    {
        return $this->daYun;
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getAge()
    {
        return $this->age;
    }
    public function getLunar()
    {
        return $this->lunar;
    }
    public function isForward()
    {
        return $this->forward;
    }
    public function getGanZhi()
    {
        $offset = LunarUtil::getJiaZiIndex($this->lunar->getTimeInGanZhi());
        $add = $this->index + 1;
        if ($this->daYun->getIndex() > 0) {
            $add += $this->daYun->getStartAge() - 1;
        }
        $offset += $this->forward ? $add : -$add;
        $size = count(LunarUtil::$JIA_ZI);
        while ($offset < 0) {
            $offset += $size;
        }
        $offset %= $size;
        return LunarUtil::$JIA_ZI[$offset];
    }
    public function getXun()
    {
        return LunarUtil::getXun($this->getGanZhi());
    }
    public function getXunKong()
    {
        return LunarUtil::getXunKong($this->getGanZhi());
    }
}
class Yun
{
    private $gender;
    private $startYear;
    private $startMonth;
    private $startDay;
    private $startHour;
    private $forward;
    private $lunar;
    public function __construct($eightChar, $gender, $sect)
    {
        $this->lunar = $eightChar->getLunar();
        $this->gender = $gender;
        $yang = 0 == $this->lunar->getYearGanIndexExact() % 2;
        $man = 1 == $gender;
        $this->forward = ($yang && $man) || (!$yang && !$man);
        $this->computeStart($sect);
    }
    private function computeStart($sect)
    {
        $prev = $this->lunar->getPrevJie();
        $next = $this->lunar->getNextJie();
        $current = $this->lunar->getSolar();
        $start = $this->forward ? $current : $prev->getSolar();
        $end = $this->forward ? $next->getSolar() : $current;
        $hour = 0;
        if (2 == $sect) {
            $minutes = $end->subtractMinute($start);
            $year = (int)($minutes / 4320);
            $minutes -= $year * 4320;
            $month = (int)($minutes / 360);
            $minutes -= $month * 360;
            $day = (int)($minutes / 12);
            $minutes -= $day * 12;
            $hour = $minutes * 2;
        } else {
            $endTimeZhiIndex = ($end->getHour() == 23) ? 11 : LunarUtil::getTimeZhiIndex(substr($end->toYmdHms(), 11, 5));
            $startTimeZhiIndex = ($start->getHour() == 23) ? 11 : LunarUtil::getTimeZhiIndex(substr($start->toYmdHms(), 11, 5));
            $hourDiff = $endTimeZhiIndex - $startTimeZhiIndex;
            $dayDiff = $end->subtract($start);
            if ($hourDiff < 0) {
                $hourDiff += 12;
                $dayDiff--;
            }
            $monthDiff = (int)($hourDiff * 10 / 30);
            $month = $dayDiff * 4 + $monthDiff;
            $day = $hourDiff * 10 - $monthDiff * 30;
            $year = (int)($month / 12);
            $month = $month - $year * 12;
        }
        $this->startYear = $year;
        $this->startMonth = $month;
        $this->startDay = $day;
        $this->startHour = $hour;
    }
    public function getGender()
    {
        return $this->gender;
    }
    public function getStartYear()
    {
        return $this->startYear;
    }
    public function getStartMonth()
    {
        return $this->startMonth;
    }
    public function getStartDay()
    {
        return $this->startDay;
    }
    public function getStartHour()
    {
        return $this->startHour;
    }
    public function isForward()
    {
        return $this->forward;
    }
    public function getLunar()
    {
        return $this->lunar;
    }
    public function getStartSolar()
    {
        $solar = $this->lunar->getSolar();
        $solar = $solar->nextYear($this->startYear);
        $solar = $solar->nextMonth($this->startMonth);
        $solar = $solar->next($this->startDay);
        return $solar->nextHour($this->startHour);
    }
    public function getDaYun()
    {
        return $this->getDaYunBy(10);
    }
    public function getDaYunBy($n)
    {
        $l = array();
        for ($i = 0; $i < $n; $i++) {
            $l[] = new DaYun($this, $i);
        }
        return $l;
    }
}
class FotoFestival
{
    private $name;
    private $result;
    private $everyMonth;
    private $remark;
    function __construct($name, $result = null, $everyMonth = false, $remark = null)
    {
        $this->name = $name;
        $this->result = null == $result ? '' : $result;
        $this->everyMonth = $everyMonth;
        $this->remark = null == $remark ? '' : $remark;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getResult()
    {
        return $this->result;
    }
    public function isEveryMonth()
    {
        return $this->everyMonth;
    }
    public function getRemark()
    {
        return $this->remark;
    }
    public function toString()
    {
        return $this->name;
    }
    public function toFullString()
    {
        $s = $this->name;
        if (null != $this->result && strlen($this->result) > 0) {
            $s .= ' ' . $this->result;
        }
        if (null != $this->remark && strlen($this->remark) > 0) {
            $s .= ' ' . $this->remark;
        }
        return $s;
    }
    public function __toString()
    {
        return $this->toString();
    }
}
class TaoFestival
{
    private $name;
    private $remark;
    function __construct($name, $remark = null)
    {
        $this->name = $name;
        $this->remark = null == $remark ? '' : $remark;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getRemark()
    {
        return $this->remark;
    }
    public function toString()
    {
        return $this->name;
    }
    public function toFullString()
    {
        $s = $this->name;
        if (null != $this->remark && strlen($this->remark) > 0) {
            $s .= '[' . $this->remark . ']';
        }
        return $s;
    }
    public function __toString()
    {
        return $this->toString();
    }
}
class Foto
{
    public static $DEAD_YEAR = -543;
    private $lunar;
    function __construct(Lunar $lunar)
    {
        $this->lunar = $lunar;
    }
    public static function fromLunar($lunar)
    {
        return new Foto($lunar);
    }
    public static function fromYmdHms($year, $month, $day, $hour, $minute, $second)
    {
        return Foto::fromLunar(Lunar::fromYmdHms($year + Foto::$DEAD_YEAR - 1, $month, $day, $hour, $minute, $second));
    }
    public static function fromYmd($year, $month, $day)
    {
        return Foto::fromYmdHms($year, $month, $day, 0, 0, 0);
    }
    public function getLunar()
    {
        return $this->lunar;
    }
    public function getYear()
    {
        $sy = $this->lunar->getSolar()->getYear();
        $y = $sy - Foto::$DEAD_YEAR;
        if ($sy == $this->lunar->getYear()) {
            $y++;
        }
        return $y;
    }
    public function getMonth()
    {
        return $this->lunar->getMonth();
    }
    public function getDay()
    {
        return $this->lunar->getDay();
    }
    public function getYearInChinese()
    {
        $y = $this->getYear() . '';
        $s = '';
        for ($i = 0, $j = strlen($y); $i < $j; $i++) {
            $s .= LunarUtil::$NUMBER[ord(substr($y, $i, 1)) - 48];
        }
        return $s;
    }
    public function getMonthInChinese()
    {
        return $this->lunar->getMonthInChinese();
    }
    public function getDayInChinese()
    {
        return $this->lunar->getDayInChinese();
    }
    public function getFestivals()
    {
        return FotoUtil::getFestivals(abs($this->getMonth()) . '-' . $this->getDay());
    }
    public function getOtherFestivals()
    {
        $l = array();
        $key = $this->getMonth() . '-' . $this->getDay();
        if (!empty(FotoUtil::$OTHER_FESTIVAL[$key])) {
            foreach (FotoUtil::$OTHER_FESTIVAL[$key] as $f) {
                $l[] = $f;
            }
        }
        return $l;
    }
    public function isMonthZhai()
    {
        $m = $this->getMonth();
        return 1 == $m || 5 == $m || 9 == $m;
    }
    public function isDayYangGong()
    {
        foreach ($this->getFestivals() as $f) {
            if (strcmp('杨公忌', $f->getName()) == 0) {
                return true;
            }
        }
        return false;
    }
    public function isDayZhaiShuoWang()
    {
        $d = $this->getDay();
        return 1 == $d || 15 == $d;
    }
    public function isDayZhaiSix()
    {
        $d = $this->getDay();
        if (8 == $d || 14 == $d || 15 == $d || 23 == $d || 29 == $d || 30 == $d) {
            return true;
        } else if (28 == $d) {
            $m = LunarMonth::fromYm($this->lunar->getYear(), $this->getMonth());
            return null != $m && 30 != $m->getDayCount();
        }
        return false;
    }
    public function isDayZhaiTen()
    {
        $d = $this->getDay();
        return 1 == $d || 8 == $d || 14 == $d || 15 == $d || 18 == $d || 23 == $d || 24 == $d || 28 == $d || 29 == $d || 30 == $d;
    }
    public function isDayZhaiGuanYin()
    {
        $k = $this->getMonth() . '-' . $this->getDay();
        foreach (FotoUtil::$DAY_ZHAI_GUAN_YIN as $d) {
            if (strcmp($k, $d) == 0) {
                return true;
            }
        }
        return false;
    }
    public function getXiu()
    {
        return FotoUtil::getXiu($this->getMonth(), $this->getDay());
    }
    public function getXiuLuck()
    {
        return LunarUtil::$XIU_LUCK[$this->getXiu()];
    }
    public function getXiuSong()
    {
        return LunarUtil::$XIU_SONG[$this->getXiu()];
    }
    public function getZheng()
    {
        return LunarUtil::$ZHENG[$this->getXiu()];
    }
    public function getAnimal()
    {
        return LunarUtil::$ANIMAL[$this->getXiu()];
    }
    public function getGong()
    {
        return LunarUtil::$GONG[$this->getXiu()];
    }
    public function getShou()
    {
        return LunarUtil::$SHOU[$this->getGong()];
    }
    public function toString()
    {
        return sprintf('%s年%s月%s', $this->getYearInChinese(), $this->getMonthInChinese(), $this->getDayInChinese());
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        $s = $this->toString();
        foreach ($this->getFestivals() as $f) {
            $s .= ' (' . $f . ')';
        }
        return $s;
    }
}
class Tao
{
    public static $BIRTH_YEAR = -2697;
    private $lunar;
    function __construct(Lunar $lunar)
    {
        $this->lunar = $lunar;
    }
    public static function fromLunar($lunar)
    {
        return new Tao($lunar);
    }
    public static function fromYmdHms($year, $month, $day, $hour, $minute, $second)
    {
        return Tao::fromLunar(Lunar::fromYmdHms($year + Tao::$BIRTH_YEAR, $month, $day, $hour, $minute, $second));
    }
    public static function fromYmd($year, $month, $day)
    {
        return Tao::fromYmdHms($year, $month, $day, 0, 0, 0);
    }
    public function getLunar()
    {
        return $this->lunar;
    }
    public function getYear()
    {
        return $this->lunar->getYear() - Tao::$BIRTH_YEAR;
    }
    public function getMonth()
    {
        return $this->lunar->getMonth();
    }
    public function getDay()
    {
        return $this->lunar->getDay();
    }
    public function getYearInChinese()
    {
        $y = $this->getYear() . '';
        $s = '';
        for ($i = 0, $j = strlen($y); $i < $j; $i++) {
            $s .= LunarUtil::$NUMBER[ord(substr($y, $i, 1)) - 48];
        }
        return $s;
    }
    public function getMonthInChinese()
    {
        return $this->lunar->getMonthInChinese();
    }
    public function getDayInChinese()
    {
        return $this->lunar->getDayInChinese();
    }
    public function getFestivals()
    {
        $l = TaoUtil::getFestivals($this->getMonth() . '-' . $this->getDay());
        $jq = $this->lunar->getJieQi();
        if (strcmp('冬至', $jq) === 0) {
            $l[] = new TaoFestival('元始天尊圣诞');
        } else if (strcmp('夏至', $jq) === 0) {
            $l[] = new TaoFestival('灵宝天尊圣诞');
        }
        if (!empty(TaoUtil::$BA_JIE[$jq])) {
            $l[] = new TaoFestival(TaoUtil::$BA_JIE[$jq]);
        }
        $gz = $this->lunar->getDayInGanZhi();
        if (!empty(TaoUtil::$BA_HUI[$gz])) {
            $l[] = new TaoFestival(TaoUtil::$BA_HUI[$gz]);
        }
        return $l;
    }
    private function isDayIn($days)
    {
        $md = $this->getMonth() . '-' . $this->getDay();
        foreach ($days as $d) {
            if (strcmp($md, $d) === 0) {
                return true;
            }
        }
        return false;
    }
    public function isDaySanHui()
    {
        return $this->isDayIn(TaoUtil::$SAN_HUI);
    }
    public function isDaySanYuan()
    {
        return $this->isDayIn(TaoUtil::$SAN_YUAN);
    }
    public function isDayWuLa()
    {
        return $this->isDayIn(TaoUtil::$WU_LA);
    }
    public function isDayBaJie()
    {
        return !empty(TaoUtil::$BA_JIE[$this->lunar->getJieQi()]);
    }
    public function isDayBaHui()
    {
        return !empty(TaoUtil::$BA_HUI[$this->lunar->getDayInGanZhi()]);
    }
    public function isDayMingWu()
    {
        return strcmp('戊', $this->lunar->getDayGan()) == 0;
    }
    public function isDayAnWu()
    {
        return strcmp($this->lunar->getDayZhi(), TaoUtil::$AN_WU[abs($this->getMonth()) - 1]) === 0;
    }
    public function isDayWu()
    {
        return $this->isDayMingWu() || $this->isDayAnWu();
    }
    public function isDayTianShe()
    {
        $ret = false;
        $mz = $this->lunar->getMonthZhi();
        $dgz = $this->lunar->getDayInGanZhi();
        if (strpos('寅卯辰', $mz) !== false) {
            if ('戊寅' === $dgz) {
                $ret = true;
            }
        } else if (strpos('巳午未', $mz) !== false) {
            if ('甲午' === $dgz) {
                $ret = true;
            }
        } else if (strpos('申酉戌', $mz) !== false) {
            if ('戊申' === $dgz) {
                $ret = true;
            }
        } else if (strpos('亥子丑', $mz) !== false) {
            if ('甲子' === $dgz) {
                $ret = true;
            }
        }
        return $ret;
    }
    public function toString()
    {
        return sprintf('%s年%s月%s', $this->getYearInChinese(), $this->getMonthInChinese(), $this->getDayInChinese());
    }
    public function __toString()
    {
        return $this->toString();
    }
    public function toFullString()
    {
        return sprintf('道歷%s年，天運%s年，%s月，%s日。%s月%s日，%s時。', $this->getYearInChinese(), $this->lunar->getYearInGanZhi(), $this->lunar->getMonthInGanZhi(), $this->lunar->getDayInGanZhi(), $this->getMonthInChinese(), $this->getDayInChinese(), $this->lunar->getTimeZhi());
    }
}
