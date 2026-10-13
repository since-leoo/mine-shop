import { View, Text, Button, Image } from '@tarojs/components';
import Taro, { useDidShow, useDidHide } from '@tarojs/taro';
import { useState, useCallback, useRef } from 'react';
import { isLoggedIn } from '../../../common/auth';
import { redirectToLogin } from '../../../common/auth-guard';
import { isH5, isMiniProgram } from '../../../common/platform';
import { fetchPerson, updateProfile, uploadImage } from '../../../services/usercenter/fetchPerson';
import { bindPhoneNumber } from '../../../services/usercenter/authorizeProfile';
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
  const [loadError, setLoadError] = useState('');
  const requestRef = useRef(0);
  useDidHide(() => { ++requestRef.current; });
  const [info, setInfo] = useState<PersonInfo>({
    avatarUrl: '',
    nickName: '',
    phoneNumber: '',
    gender: 0,
  });
  const [submitting, setSubmitting] = useState(false);
  const submittingRef = useRef(false);

  const loadData = useCallback(() => {
    const sequence = ++requestRef.current;
    setLoading(true);
    setLoadError('');
    fetchPerson()
      .then((res: any) => {
        if (sequence !== requestRef.current) return;
        setInfo({
          avatarUrl: res.avatarUrl || '',
          nickName: res.nickName || res.nickname || '',
          phoneNumber: res.phoneNumber || res.phone || '',
          gender: res.gender ?? 0,
        });
      })
      .catch((error: any) => { if (sequence === requestRef.current) setLoadError(error?.msg || '个人资料加载失败'); })
      .finally(() => { if (sequence === requestRef.current) setLoading(false); });
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

  const handleChooseAvatar = useCallback(async (e: any) => {
    const avatarUrl = e?.detail?.avatarUrl;
    if (!isMiniProgram() || !avatarUrl || submittingRef.current) return;

    // chooseAvatar 返回临时文件，上传完成后才保存可长期访问的头像地址。
    submittingRef.current = true;
    setSubmitting(true);
    try {
      const url = await uploadImage(avatarUrl);
      await updateProfile({ avatarUrl: url });
      Taro.showToast({ title: '头像已更新', icon: 'success' });
      loadData();
    } catch (err: any) {
      Taro.showToast({ title: err?.msg || '头像更新失败', icon: 'none' });
    } finally {
      submittingRef.current = false;
      setSubmitting(false);
    }
  }, [loadData]);

  const handleGetPhoneNumber = useCallback((e: any) => {
    if (!isMiniProgram() || submittingRef.current) return;

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

    submittingRef.current = true;
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
        submittingRef.current = false;
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
            <Text className="person-info__auth-title">完善微信资料</Text>
            <Text className="person-info__auth-sub">选择头像，点击昵称填写，也可授权绑定手机号</Text>
          </View>
          <View className="person-info__auth-actions">
            <Button
              className={`person-info__auth-btn ${submitting ? 'person-info__auth-btn--disabled' : ''}`}
              openType="chooseAvatar"
              onChooseAvatar={handleChooseAvatar}
              disabled={submitting}
            >
              <Text className="person-info__auth-btn-text">选择微信头像</Text>
            </Button>
            <Button
              className={`person-info__auth-btn person-info__auth-btn--outline ${submitting ? 'person-info__auth-btn--disabled' : ''}`}
              openType="getPhoneNumber"
              onGetPhoneNumber={handleGetPhoneNumber}
              disabled={submitting}
            >
              <Text className="person-info__auth-btn-text person-info__auth-btn-text--outline">手机号授权绑定</Text>
            </Button>
          </View>
        </View>
      )}

      <View className="person-info__avatar-section" onClick={isMiniProgram() ? undefined : handleChangeAvatar}>
        {isMiniProgram() ? (
          <Button
            className="person-info__avatar-chooser"
            openType="chooseAvatar"
            onChooseAvatar={handleChooseAvatar}
            disabled={submitting}
            ariaLabel="选择头像"
          />
        ) : null}
        <View className="person-info__avatar-row">
          <Text className="person-info__label">头像</Text>
          <View className="person-info__avatar-wrap">
            {info.avatarUrl ? (
              <Image className="person-info__avatar" src={info.avatarUrl} mode="aspectFill" onError={() => setInfo(current => ({ ...current, avatarUrl: '' }))} />
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
      {!!loadError && !loading && <View className="person-info__error" onClick={loadData}><Text>{loadError}，点击重试</Text></View>}
      {!loading && !loadError && (
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
