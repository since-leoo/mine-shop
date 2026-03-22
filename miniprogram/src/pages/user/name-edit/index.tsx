import { View, Text, Input } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useCallback } from 'react';
import { updateProfile } from '../../../services/usercenter/fetchPerson';
import PageNav from '../../../components/page-nav';
import './index.scss';

export default function NameEdit() {
  const router = useRouter();
  const initialName = decodeURIComponent(router.params.name || '');

  const [nickname, setNickname] = useState(initialName);
  const [saving, setSaving] = useState(false);

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
    if (saving) return;

    setSaving(true);
    updateProfile({ nickName: trimmed })
      .then(() => {
        Taro.showToast({ title: '修改成功', icon: 'success' });
        setTimeout(() => Taro.navigateBack(), 800);
      })
      .catch(() => {
        Taro.showToast({ title: '修改失败', icon: 'none' });
      })
      .finally(() => setSaving(false));
  }, [nickname, saving]);

  return (
    <View className="name-edit">
      <PageNav title="修改昵称" />
      <View className="name-edit__card">
        <Text className="name-edit__label">昵称</Text>
        <Input
          className="name-edit__input"
          type="text"
          placeholder="请输入昵称"
          maxlength={20}
          value={nickname}
          onInput={(e) => setNickname(e.detail.value)}
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
