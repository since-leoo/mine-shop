import { View, Text, Input } from '@tarojs/components';
import Taro, { useRouter, useDidHide, useDidShow } from '@tarojs/taro';
import { useState, useCallback, useRef } from 'react';
import { updateProfile } from '../../../services/usercenter/fetchPerson';
import PageNav from '../../../components/page-nav';
import { isMiniProgram } from '../../../common/platform';
import './index.scss';

export default function NameEdit() {
  const router = useRouter();
  // H5 路由保留编码后的 query；外部直达链接中的裸百分号也不能使页面崩溃。
  let initialName = router.params.name || '';
  try { initialName = decodeURIComponent(initialName); } catch { /* 保留不能解码的原始昵称。 */ }

  const [nickname, setNickname] = useState(initialName);
  const [saving, setSaving] = useState(false);
  const savingRef = useRef(false);
  const activeRef = useRef(true);
  useDidShow(() => { activeRef.current = true; });
  useDidHide(() => { activeRef.current = false; });

  const handleSave = useCallback(() => {
    const trimmed = nickname.trim();
    if (!trimmed) {
      Taro.showToast({ title: '请输入昵称', icon: 'none' });
      return;
    }
    if (trimmed.length > 20) {
      Taro.showToast({ title: '昵称不能超过20个字符', icon: 'none' });
      return;
    }
    if (savingRef.current) return;

    savingRef.current = true;
    setSaving(true);
    updateProfile({ nickName: trimmed })
      .then(async () => {
        if (!activeRef.current) return;
        Taro.showToast({ title: '修改成功', icon: 'success' });
        await Taro.navigateBack().catch(() => Taro.redirectTo({ url: '/pages/user/person-info/index' }));
      })
      .catch(() => {
        Taro.showToast({ title: '修改失败', icon: 'none' });
      })
      .finally(() => { savingRef.current = false; setSaving(false); });
  }, [nickname, saving]);

  return (
    <View className="name-edit">
      <PageNav title="修改昵称" />
      <View className="name-edit__card">
        <Text className="name-edit__label">昵称</Text>
        <Input
          className="name-edit__input"
          type={isMiniProgram() ? 'nickname' : 'text'}
          placeholder="请输入昵称"
          maxlength={20}
          value={nickname}
          onInput={(e) => setNickname(e.detail.value)}
          onBlur={isMiniProgram() ? (e) => setNickname(e.detail.value) : undefined}
          focus
        />
        <Text className="name-edit__count">{nickname.length}/20</Text>
      </View>

      <View className="name-edit__btn-wrap">
        <View
          className={`name-edit__save-btn ${saving ? 'name-edit__save-btn--disabled' : ''}`}
          onClick={handleSave}
        >
          <Text className="name-edit__save-btn-text">
            {saving ? '保存中...' : '保存'}
          </Text>
        </View>
      </View>
    </View>
  );
}
