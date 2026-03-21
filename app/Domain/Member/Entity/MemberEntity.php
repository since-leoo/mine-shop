<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace App\Domain\Member\Entity;

use App\Domain\Member\Contract\MemberInput;
use App\Domain\Member\Contract\ProfileAuthorizeInput;
use App\Domain\Member\Contract\ProfileUpdateInput;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Carbon\Carbon;

/**
 * 濠电姷鏁搁崑鐐差焽濞嗘挸瑙﹂悗锝庡枟閸ゅ苯螖閿濆懎鏆欑紒鐘靛枛閺岀喖骞嗛弶鍟冩捇鏌涢妶鍛偓褰掑箞閵娿儺娼ㄩ柛鈩冾殔椤亪姊虹粙娆剧劸闁稿﹥绻堝?
 */
final class MemberEntity
{
    private int $id = 0;

    private ?string $openid = null;

    private ?string $unionid = null;

    private ?string $nickname = null;

    private ?string $avatar = null;

    private ?string $gender = null;

    private ?string $phone = null;

    private ?string $password = null;

    private ?Carbon $birthday = null;

    private ?string $city = null;

    private ?string $province = null;

    private ?string $district = null;

    private ?string $street = null;

    private ?string $regionPath = null;

    private ?string $country = null;

    private ?string $level = null;

    private ?int $levelId = null;

    private ?int $growthValue = null;

    private ?string $status = null;

    private ?string $source = null;

    private ?string $remark = null;

    private ?string $inviteCode = null;

    private ?int $referrerId = null;

    private ?Carbon $lastLoginAt;

    private string $lastLoginIp = '';

    /** @var int[] */
    private array $tagIds = [];

    private ?MemberWalletEntity $wallet = null;

    /**
     * @var array<string, bool>
     */
    private array $dirtyFields = [];

    /**
     * 闂傚倸鍊风粈渚€骞夐敍鍕殰婵°倕鍟伴惌娆撴煙鐎电啸缁惧彞绮欓弻鐔煎箲閹邦剛鍘梺绋款儐閿曘垽骞冨鈧幃娆戞崉娓氼垱顥嶇紓鍌欒閸嬫捇鎮楅敐搴℃灍闁绘挻鐩弻娑氫沪閸撗咁吋濠电偛鍚嬮崝妤呮儉椤忓牜鏁囬柣鎰版涧閻撶喖姊烘潪鎵槮缂佸鎸抽敐鐐测攽鐎ｅ灚鏅ｉ梺缁樺姇濡﹤危婵犳碍鈷掑ù锝咁潟閳ь兘鍋撻梺?DTO闂傚倸鍊烽悞锔锯偓绗涘懐鐭欓柟杈鹃檮閸ゆ劖銇勯弽顐粶闁肩缍婇弻鐔虹磼閵忕姵鐏嶉梺缁樺浮缁犳牠寮婚弴鐔虹鐟滃秹骞婇幇鐗堝亗濞撴埃鍋撻柡宀嬬秮閹垽宕滄笟鍥ㄐ滈梻浣侯攰濞呮洟骞戦崶顑锯偓浣糕枎閹惧啿宓嗛梺缁橆焽閺佹悂鏁嶉悙宸富闁靛牆妫欓悡銉╂煟閵娧冨幋鐎?
     */
    public function create(MemberInput $dto): self
    {
        $this->setNickname($dto->getNickname());
        $this->setAvatar($dto->getAvatar());
        $this->setGender($dto->getGender());
        $this->setPhone($dto->getPhone());
        $this->setBirthday($dto->getBirthday());
        $this->setCity($dto->getCity());
        $this->setProvince($dto->getProvince());
        $this->setDistrict($dto->getDistrict());
        $this->setStreet($dto->getStreet());
        $this->setRegionPath($dto->getRegionPath());
        $this->setCountry($dto->getCountry());
        $this->setLevel($dto->getLevel() ?? 'bronze');
        $this->setGrowthValue($dto->getGrowthValue());
        $this->setStatus($dto->getStatus() ?? 'active');
        $this->setSource($dto->getSource() ?? 'admin');
        $this->setRemark($dto->getRemark());
        $this->setTagIds($dto->getTagIds());

        return $this;
    }

    /**
     * 闂傚倸鍊风粈渚€骞栭鈷氭椽濡舵径瀣槐闂侀潧艌閺呮盯鎷戦悢灏佹斀闁绘ɑ褰冮顏堟煕鐎ｎ偓鑰块柟顔斤耿閹瑧鎹勬笟顖涱棈缂傚倷璁查崑鎾绘倵閿濆骸鏋熼柣鎾寸洴閺屾稓浠﹂崜褏顓煎┑鐐插悑閸旀鎯€椤忓牜鏁囬柣鎰版涧閻撶喖姊烘潪鎵槮缂佸鎸抽敐鐐测攽鐎ｅ灚鏅ｉ梺缁樺姇濡﹤危婵犳碍鈷掑ù锝咁潟閳ь兘鍋撻梺?DTO闂傚倸鍊烽悞锔锯偓绗涘懐鐭欓柟杈鹃檮閸ゆ劖銇勯弽顐粶闁肩缍婇弻鐔虹磼閵忕姵鐏嶉梺缁樺浮缁犳牠寮婚弴鐔虹鐟滃秹骞婇幇鐗堝亗濞撴埃鍋撻柡宀嬬秮閹垽宕滄笟鍥ㄐ滈梻浣侯攰濞呮洟骞戦崶顑锯偓浣糕枎閹惧啿宓嗛梺缁橆焽閺佹悂鏁嶉悙宸富闁靛牆妫欓悡銉╂煟閵娧冨幋鐎?
     */
    public function update(MemberInput $dto): self
    {
        $dto->getNickname() !== null && $this->setNickname($dto->getNickname());
        $dto->getAvatar() !== null && $this->setAvatar($dto->getAvatar());
        $dto->getGender() !== null && $this->setGender($dto->getGender());
        $dto->getPhone() !== null && $this->setPhone($dto->getPhone());
        $dto->getBirthday() !== null && $this->setBirthday($dto->getBirthday());
        $dto->getCity() !== null && $this->setCity($dto->getCity());
        $dto->getProvince() !== null && $this->setProvince($dto->getProvince());
        $dto->getDistrict() !== null && $this->setDistrict($dto->getDistrict());
        $dto->getStreet() !== null && $this->setStreet($dto->getStreet());
        $dto->getRegionPath() !== null && $this->setRegionPath($dto->getRegionPath());
        $dto->getCountry() !== null && $this->setCountry($dto->getCountry());
        $dto->getLevel() !== null && $this->setLevel($dto->getLevel());
        $dto->getGrowthValue() !== null && $this->setGrowthValue($dto->getGrowthValue());
        $dto->getStatus() !== null && $this->setStatus($dto->getStatus());
        $dto->getSource() !== null && $this->setSource($dto->getSource());
        $dto->getRemark() !== null && $this->setRemark($dto->getRemark());

        return $this;
    }

    /**
     * 闂傚倸鍊风粈渚€骞栭鈷氭椽濡舵径瀣槐闂侀潧艌閺呮盯鎷戦悢灏佹斀闁绘ê寮舵径鍕煕鐎ｎ偄濮嶉柡灞诲€濆畷顐﹀Ψ椤旇姤鐦滈梻浣侯焾椤戝棝骞愭繝姘闁告侗鍨虫す鎶芥倵閿濆骸浜濋柡澶岊焾閳规垿鎮欓弶鎴犱桓闂佽鎮傜粻鏍х暦閺囥垹围濠㈣泛锕ㄩ幗鏇㈡⒑缂佹ɑ鐓ラ柣銊︾箞瀵?
     */
    public function updateStatus(string $status): self
    {
        if (! \in_array($status, ['active', 'inactive', 'banned'], true)) {
            throw new BusinessException(ResultCode::FAIL, 'Invalid member status');
        }

        $this->setStatus($status);
        return $this;
    }

    /**
     * 闂傚倸鍊风粈渚€骞夐敓鐘冲殞濡わ絽鍟€氬銇勯幒鎴濐伌闁轰礁妫濋弻锝夊箛椤掍焦鍎撻梺鍛婂姀閸嬫捇姊绘担瑙勫仩闁稿寒鍣ｅ鏌ュ煛閸涱厾顦銈嗘磵閸嬫捇鏌熼鎸庣【闁宠棄顦灒濞撴凹鍨遍鍕⒑閼姐倕校闁告梹顨婂畷浼村冀瑜滈崵鏇熴亜閹烘垵鈧綊宕伴崱娑欑厱闁哄洢鍔岄獮鎴︽煃?
     *
     * @param int[] $tagIds
     */
    public function syncTags(array $tagIds): self
    {
        $this->setTagIds($tagIds);
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    public function setOpenid(?string $openid): void
    {
        $this->openid = $openid;
        $this->markDirty('openid');
    }

    public function getUnionid(): ?string
    {
        return $this->unionid;
    }

    public function setUnionid(?string $unionid): void
    {
        $this->unionid = $unionid;
        $this->markDirty('unionid');
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): void
    {
        $this->nickname = $nickname;
        $this->markDirty('nickname');
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
        $this->markDirty('avatar');
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(int|string|null $gender): void
    {
        \is_int($gender) && $gender = match ($gender) {
            1 => 'male',
            2 => 'female',
            default => 'unknown',
        };
        $this->gender = $gender;
        $this->markDirty('gender');
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
        $this->markDirty('phone');
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        if ($password === null || trim($password) === '') {
            return;
        }

        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->markDirty('password');
    }

    public function setHashedPassword(?string $password): void
    {
        $this->password = $password;
        if ($password !== null) {
            $this->markDirty('password');
        }
    }

    public function hasPassword(): bool
    {
        return $this->password !== null && $this->password !== '';
    }

    public function verifyPassword(string $password): bool
    {
        return $this->hasPassword() && password_verify($password, (string) $this->password);
    }


    public function resetLoginPassword(string $password): self
    {
        $this->setPassword($password);

        return $this;
    }

    public function loginByPassword(string $password, ?string $ip = null): self
    {
        if (! $this->verifyPassword($password)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, 'Invalid phone or password');
        }

        $this->setLastLoginAt(now());
        if ($ip !== null && trim($ip) !== '') {
            $this->setLastLoginIp($ip);
        }
        $this->setSource($this->source ?? 'h5');

        return $this;
    }

    public function getBirthday(): ?Carbon
    {
        return $this->birthday;
    }

    public function setBirthday(?Carbon $birthday): void
    {
        $this->birthday = $birthday;
        $this->markDirty('birthday');
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
        $this->markDirty('city');
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function setProvince(?string $province): void
    {
        $this->province = $province;
        $this->markDirty('province');
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): void
    {
        $this->district = $district;
        $this->markDirty('district');
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
        $this->markDirty('street');
    }

    public function getRegionPath(): ?string
    {
        return $this->regionPath;
    }

    public function setRegionPath(?string $regionPath): void
    {
        $this->regionPath = $regionPath;
        $this->markDirty('region_path');
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
        $this->markDirty('country');
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): void
    {
        $this->level = $level;
        $this->markDirty('level');
    }

    public function getLevelId(): ?int
    {
        return $this->levelId;
    }

    public function setLevelId(?int $levelId): void
    {
        $this->levelId = $levelId;
        $this->markDirty('level_id');
    }

    public function getGrowthValue(): ?int
    {
        return $this->growthValue;
    }

    public function setGrowthValue(?int $growthValue): void
    {
        $this->growthValue = $growthValue;
        $this->markDirty('growth_value');
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
        $this->markDirty('status');
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
        $this->markDirty('source');
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
        $this->markDirty('remark');
    }

    public function getInviteCode(): ?string
    {
        return $this->inviteCode;
    }

    public function setInviteCode(?string $inviteCode): void
    {
        $this->inviteCode = $inviteCode;
        $this->markDirty('invite_code');
    }

    public function getReferrerId(): ?int
    {
        return $this->referrerId;
    }

    public function setReferrerId(?int $referrerId): void
    {
        $this->referrerId = $referrerId;
        $this->markDirty('referrer_id');
    }

    public function setLastLoginAt(Carbon $now)
    {
        $this->lastLoginAt = $now;
        $this->markDirty('last_login_at');
    }

    public function getLastLoginAt(): Carbon
    {
        return $this->lastLoginAt;
    }

    public function getLastLoginIp(): string
    {
        return $this->lastLoginIp;
    }

    public function setLastLoginIp(string $ip): void
    {
        $this->lastLoginIp = $ip;
        $this->markDirty('last_login_ip');
    }

    /**
     * @return int[]
     */
    public function getTagIds(): array
    {
        return $this->tagIds;
    }

    /**
     * @param int[] $tagIds
     */
    public function setTagIds(array $tagIds): void
    {
        $this->tagIds = array_values(array_unique(array_map('intval', $tagIds)));
    }

    public function getWallet(): ?MemberWalletEntity
    {
        return $this->wallet;
    }

    public function setWallet(MemberWalletEntity $wallet): void
    {
        $this->wallet = $wallet;
    }

    public function bindPhone(string $phone): self
    {
        if (trim($phone) === '') {
            throw new \DomainException('Phone number is required');
        }

        $this->setPhone($phone);
        return $this;
    }

    /**
     * 闂傚倸鍊烽懗鍫曞箠閹捐绠规い鎰堕檮閸嬪鈹戦悩鎻掍簽闁绘帊绮欓弻娑㈩敃閵堝懏鐎荤紓浣稿閸嬨倝寮婚埄鍐ㄧ窞閻庯綆浜炴禒鎾⒑閸涘﹥灏柕鍫熸倐瀵鏁撻悩鑼槹濡炪倖鎸鹃崰鎰掗崟顖涘仩婵ǜ鍎辨慨鍌炴煙椤旀寧纭鹃柍钘夘槸铻ｅ〒姘煎灡椤斿嫰姊洪懡銈呅ｉ柛鏃€顨婂畷浼村冀瑜滈崵鏇熴亜閹烘垵鈧綊宕伴崱娑欑厱闁哄洢鍔岄獮鎴︽煃?
     */
    public function authorizeProfile(ProfileAuthorizeInput $input): self
    {
        $nickname = $input->getNickname();
        if ($nickname !== null && trim($nickname) !== '') {
            $this->setNickname($nickname);
        }

        $avatarUrl = $input->getAvatarUrl();
        if ($avatarUrl !== null && trim($avatarUrl) !== '') {
            $this->setAvatar($avatarUrl);
        }

        if ($input->getGender() !== null) {
            $this->setGender($input->getGender());
        }

        return $this;
    }

    /**
     * 濠电姷鏁搁崕鎴犲緤閽樺褰掑磼閻愯尙鐛ュ┑掳鍊曢幊搴ㄥ几娓氣偓閺屾稖绠涘顑挎睏闂佸磭绮褰掑Φ閸曨喚鐤€闁规崘娅曞▓鏌ユ⒑濞茶绨风紒顔界懇楠炲啰娑甸崪浣剐╅梺璇插閸戝綊宕ｉ崘顭戝殨闁归棿鐒﹂弲顒勬煕閺囥劌澧ù鐘冲浮閺岋綀绠涢弴鐐板摋婵犮垻鎳撻悧蹇曞垝閸懇鍋撻敐搴℃灍闁绘挻鐩弻娑氫沪閸撗咁吋濠电偛鍚嬮崝妤呮儉?
     */
    public function updateProfile(ProfileUpdateInput $input): self
    {
        $nickname = $input->getNickname();
        if ($nickname !== null && trim($nickname) !== '') {
            $this->setNickname($nickname);
        }

        $avatarUrl = $input->getAvatarUrl();
        if ($avatarUrl !== null && trim($avatarUrl) !== '') {
            $this->setAvatar($avatarUrl);
        }

        if ($input->getGender() !== null) {
            $this->setGender($input->getGender());
        }

        $phone = $input->getPhone();
        if ($phone !== null && trim($phone) !== '') {
            $this->setPhone($phone);
        }

        return $this;
    }

    public function toArray(): array
    {
        $data = [];

        foreach (array_keys($this->dirtyFields) as $field) {
            $data[$field] = match ($field) {
                'openid' => $this->openid,
                'unionid' => $this->unionid,
                'nickname' => $this->nickname,
                'avatar' => $this->avatar,
                'gender' => $this->gender,
                'phone' => $this->phone,
                'password' => $this->password,
                'birthday' => $this->birthday?->toDateString(),
                'city' => $this->city,
                'province' => $this->province,
                'district' => $this->district,
                'street' => $this->street,
                'region_path' => $this->regionPath,
                'country' => $this->country,
                'level' => $this->level,
                'level_id' => $this->levelId,
                'growth_value' => $this->growthValue,
                'status' => $this->status,
                'source' => $this->source,
                'remark' => $this->remark,
                'invite_code' => $this->inviteCode,
                'referrer_id' => $this->referrerId,
                'last_login_at' => $this->lastLoginAt?->toDateTimeString(),
                'last_login_ip' => $this->lastLoginIp,
                default => null,
            };
        }

        return $data;
    }

    public function clearDirty(): void
    {
        $this->dirtyFields = [];
    }

    private function markDirty(string $field): void
    {
        $this->dirtyFields[$field] = true;
    }
}
