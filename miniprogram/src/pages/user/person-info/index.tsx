import { View, Text, Button, Image } from '@tarojs/components';
import Taro, { useDidShow } from '@tarojs/taro';
import { useState, useCallback } from 'react';
import { isLoggedIn } from '../../../common/auth';
import { redirectToLogin } from '../../../common/auth-guard';
import { isH5, isMiniProgram } from '../../../common/platform';
import { fetchPerson, updateProfile, uploadImage } from '../../../services/usercenter/fetchPerson';
import { authorizeProfile, bindPhoneNumber } from '../../../services/usercenter/authorizeProfile';
import PageNav from '../../../components/page-nav';
import './index.scss';

interface PersonInfo {
  avatarUrl: string;
  nickName: string;
  phoneNumber: string;
  gender: number;
}

const GENDER_MAP: Record<number, string> = {
  0: '未设置',
  1: '男',
  2: '女',
};

export default function PersonInfo() {
  const [loading, setLoading] = useState(true);
  const [info, setInfo] = useState<PersonInfo>({
    avatarUrl: '',
    nickName: '',
    phoneNumber: '',
    gender: 0,
  });
  const [submitting, setSubmitting] = useState(false);

  const loadData = useCallback(() => {
    setLoading(true);
    fetchPerson()
      .then((res: any) => {
        setInfo({
          avatarUrl: res.avatarUrl || '',
          nickName: res.nickName || res.nickname || '',
          phoneNumber: res.phoneNumber || res.phone || '',
          gender: res.gender ?? 0,
        });
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  useDidShow(() => {
    if (isH5() && !isLoggedIn()) {
      redirectToLogin('/pages/user/person-info/index');
      return;
    }

    loadData();
  });

  const handleChangeAvatar = useCallback(() => {
    Taro.chooseImage({
      count: 1,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
    })
      .then((res) => {
        const tempFilePath = res.tempFilePaths[0];
        if (!tempFilePath) return;

        uploadImage(tempFilePath)
          .then((url: string) => updateProfile({ avatarUrl: url }))
          .then(() => {
            Taro.showToast({ title: '头像已更新', icon: 'success' });
            loadData();
          })
          .catch(() => {
            Taro.showToast({ title: '上传失败', icon: 'none' });
          });
      })
      .catch(() => {});
  }, [loadData]);

  const handleEditNickname = useCallback(() => {
    Taro.navigateTo({
      url: `/pages/user/name-edit/index?name=${encodeURIComponent(info.nickName)}`,
    });
  }, [info.nickName]);

  const handleUseWechatProfile = useCallback(() => {
    if (!isMiniProgram()) return;

    Taro.getUserProfile({
      desc: '用于完善会员资料',
    })
      .then((res) => {
        const nickName = res.userInfo?.nickName || '';
        const avatarUrl = res.userInfo?.avatarUrl || '';
        const payload: any = {};
        if (nickName) payload.nickname = nickName;
        if (avatarUrl) payload.avatarUrl = avatarUrl;
        if (typeof res.userInfo?.gender === 'number') payload.gender = res.userInfo.gender;
        if (Object.keys(payload).length === 0) return;

        setSubmitting(true);
        authorizeProfile(payload)
          .then(() => {
            Taro.showToast({ title: '微信资料已同步', icon: 'success' });
            loadData();
          })
          .catch((err: any) => {
            Taro.showToast({ title: err?.msg || '同步失败', icon: 'none' });
          })
          .finally(() => setSubmitting(false));
      })
      .catch(() => {});
  }, [loadData]);

  const handleGetPhoneNumber = useCallback((e: any) => {
    if (!isMiniProgram()) return;

    const detail = e?.detail || {};
    const code = detail.code || '';
    const errMsg = detail.errMsg || '';
    if (!code) {
      if (errMsg && errMsg !== 'getPhoneNumber:ok') {
        Taro.showToast({ title: '你已取消手机号授权', icon: 'none' });
      } else {
        Taro.showToast({ title: '未获取到手机号授权码', icon: 'none' });
      }
      return;
    }

    setSubmitting(true);
    Taro.showLoading({ title: '绑定中...', mask: true });
    bindPhoneNumber(code)
      .then(() => {
        Taro.showToast({ title: '手机号已绑定', icon: 'success' });
        loadData();
      })
      .catch((err: any) => {
        Taro.showToast({ title: err?.msg || '绑定失败', icon: 'none' });
      })
      .finally(() => {
        Taro.hideLoading();
        setSubmitting(false);
      });
  }, [loadData]);

  const handleChangeGender = useCallback(() => {
    Taro.showActionSheet({
      itemList: ['男', '女'],
    })
      .then((res) => {
        const gender = res.tapIndex + 1;
        updateProfile({ gender })
          .then(() => {
            Taro.showToast({ title: '已更新', icon: 'success' });
            loadData();
          })
          .catch(() => {
            Taro.showToast({ title: '更新失败', icon: 'none' });
          });
      })
      .catch(() => {});
  }, [loadData]);

  return (
    <View className="person-info warm-page-enter">
      <PageNav title="个人信息" />
      {isMiniProgram() && (
        <View className="person-info__auth-card">
          <View className="person-info__auth-title-row">
            <Text className="person-info__auth-title">微信授权</Text>
            <Text className="person-info__auth-sub">一键同步微信头像、昵称和手机号</Text>
          </View>
          <View className="person-info__auth-actions">
            <View className={`person-info__auth-btn ${submitting ? 'person-info__auth-btn--disabled' : ''}`} onClick={handleUseWechatProfile}>
              <Text className="person-info__auth-btn-text">微信头像昵称授权</Text>
            </View>
            <Button
              className={`person-info__auth-btn person-info__auth-btn--outline ${submitting ? 'person-info__auth-btn--disabled' : ''}`}
              openType="getPhoneNumber"
              onGetPhoneNumber={handleGetPhoneNumber}
            >
              <Text className="person-info__auth-btn-text person-info__auth-btn-text--outline">手机号授权绑定</Text>
            </Button>
          </View>
        </View>
      )}

      <View className="person-info__avatar-section" onClick={handleChangeAvatar}>
        <View className="person-info__avatar-row">
          <Text className="person-info__label">头像</Text>
          <View className="person-info__avatar-wrap">
            {info.avatarUrl ? (
              <Image className="person-info__avatar" src={info.avatarUrl} mode="aspectFill" />
            ) : (
              <View className="person-info__avatar person-info__avatar--empty">
                <Text className="person-info__avatar-placeholder">我</Text>
              </View>
            )}
            <Text className="person-info__arrow">›</Text>
          </View>
        </View>
      </View>

      {loading && (
        <View className="person-info__loading-wrap">
          <View className="person-info__loading-line warm-skeleton" />
          <View className="person-info__loading-line warm-skeleton" />
        </View>
      )}
      {!loading && (
        <View className="person-info__cells">
          <View className="person-info__cell" onClick={handleEditNickname}>
            <Text className="person-info__cell-label">昵称</Text>
            <View className="person-info__cell-value-wrap">
              <Text className="person-info__cell-value">{info.nickName || '未设置'}</Text>
              <Text className="person-info__cell-arrow">›</Text>
            </View>
          </View>
          <View className="person-info__cell">
            <Text className="person-info__cell-label">手机号</Text>
            <Text className="person-info__cell-value">{info.phoneNumber || '未绑定'}</Text>
          </View>
          <View className="person-info__cell person-info__cell--last" onClick={handleChangeGender}>
            <Text className="person-info__cell-label">性别</Text>
            <View className="person-info__cell-value-wrap">
              <Text className="person-info__cell-value">{GENDER_MAP[info.gender] || '未设置'}</Text>
              <Text className="person-info__cell-arrow">›</Text>
            </View>
          </View>
        </View>
      )}
    </View>
  );
}
