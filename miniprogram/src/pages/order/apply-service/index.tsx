import { View, Text, Textarea, Image, Input } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useEffect, useMemo, useState } from 'react';
import { createAfterSale, fetchAfterSaleEligibility, type AfterSaleEligibility, type AfterSaleType } from '../../../services/order/afterSale';
import { uploadImage } from '../../../services/upload';
import { AFTER_SALE_TYPE_TEXT_MAP, formatAmount, getAfterSaleTypeHint } from '../after-service/shared';
import { buildAmountInput, clampAmountByInput, clampQuantity, centsToYuanInput, computeMaxAmountByQuantity, normalizeAmountInput, resolveAmountInput } from './helpers';
import './index.scss';

const DEFAULT_REASONS = [
  '商品质量问题',
  '商品与描述不符',
  '少件/漏发',
  '尺寸不合适',
  '不想要了',
  '其他',
];

export default function ApplyService() {
  const router = useRouter();
  const orderId = Number(router.params.orderId || 0);
  const orderItemId = Number(router.params.orderItemId || 0);
  const orderNo = router.params.orderNo || '';
  const productName = decodeURIComponent(router.params.productName || '');
  const skuName = decodeURIComponent(router.params.skuName || '');

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [eligibility, setEligibility] = useState<AfterSaleEligibility | null>(null);
  const [serviceType, setServiceType] = useState<AfterSaleType>('refund_only');
  const [reason, setReason] = useState(DEFAULT_REASONS[0]);
  const [description, setDescription] = useState('');
  const [images, setImages] = useState<string[]>([]);
  const [quantity, setQuantity] = useState(1);
  const [amountInput, setAmountInput] = useState('0.00');

  useEffect(() => {
    if (!orderId || !orderItemId) {
      setLoading(false);
      return;
    }

    setLoading(true);
    fetchAfterSaleEligibility({ orderId, orderItemId })
      .then((data: AfterSaleEligibility) => {
        setEligibility(data);
        setQuantity(clampQuantity(data.maxQuantity || 1, data.maxQuantity || 1));
        setAmountInput(buildAmountInput(data.maxAmount || 0));
        if (Array.isArray(data.types) && data.types.length > 0) {
          setServiceType(data.types[0]);
        }
      })
      .catch((error) => {
        Taro.showToast({ title: error?.msg || '获取售后资格失败', icon: 'none' });
      })
      .finally(() => setLoading(false));
  }, [orderId, orderItemId]);

  const maxAmountByQuantity = useMemo(() => {
    if (!eligibility) {
      return 0;
    }

    return computeMaxAmountByQuantity(eligibility.maxAmount, eligibility.maxQuantity, quantity);
  }, [eligibility, quantity]);

  const submitAmount = useMemo(() => {
    if (serviceType === 'exchange') {
      return 0;
    }

    return clampAmountByInput(amountInput, maxAmountByQuantity);
  }, [amountInput, maxAmountByQuantity, serviceType]);

  const canSubmit = useMemo(() => {
    if (!eligibility?.canApply || submitting || uploading) {
      return false;
    }

    if (serviceType === 'exchange') {
      return quantity >= 1;
    }

    return quantity >= 1 && submitAmount > 0;
  }, [eligibility, quantity, serviceType, submitAmount, submitting, uploading]);

  const typeHint = useMemo(() => getAfterSaleTypeHint(serviceType), [serviceType]);

  const handleChooseImage = async () => {
    const remain = 9 - images.length;
    if (remain <= 0) {
      Taro.showToast({ title: '最多上传9张图片', icon: 'none' });
      return;
    }

    try {
      const chooseResult = await Taro.chooseImage({
        count: remain,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
      });
      const filePaths = chooseResult.tempFilePaths || [];
      if (!filePaths.length) return;

      setUploading(true);
      Taro.showLoading({ title: '图片上传中' });
      const urls = await Promise.all(filePaths.map((item) => uploadImage(item)));
      setImages((prev) => [...prev, ...urls]);
    }
    catch (error: any) {
      Taro.showToast({ title: error?.msg || '上传图片失败', icon: 'none' });
    }
    finally {
      Taro.hideLoading();
      setUploading(false);
    }
  };

  const handleRemoveImage = (index: number) => {
    setImages((prev) => prev.filter((_, currentIndex) => currentIndex !== index));
  };

  const handlePreviewImage = (current: string) => {
    Taro.previewImage({ current, urls: images });
  };

  const handleQuantityChange = (nextValue: number) => {
    if (!eligibility) {
      return;
    }

    const nextQuantity = clampQuantity(nextValue, eligibility.maxQuantity);
    setQuantity(nextQuantity);

    if (serviceType !== 'exchange') {
      const nextMaxAmount = computeMaxAmountByQuantity(eligibility.maxAmount, eligibility.maxQuantity, nextQuantity);
      setAmountInput(buildAmountInput(nextMaxAmount));
    }
  };

  const handleAmountInput = (value: string) => {
    setAmountInput(normalizeAmountInput(value));
  };

  const handleAmountBlur = () => {
    if (serviceType === 'exchange') {
      setAmountInput(buildAmountInput(0));
      return;
    }

    setAmountInput(resolveAmountInput(amountInput, maxAmountByQuantity));
  };

  const handleServiceTypeChange = (nextType: AfterSaleType) => {
    setServiceType(nextType);

    if (nextType === 'exchange') {
      setAmountInput(buildAmountInput(0));
      return;
    }

    setAmountInput(buildAmountInput(maxAmountByQuantity));
  };

  const handleSubmit = async () => {
    if (!reason.trim()) {
      Taro.showToast({ title: '请选择售后原因', icon: 'none' });
      return;
    }
    if (!eligibility?.canApply) {
      Taro.showToast({ title: '当前商品暂不支持申请售后', icon: 'none' });
      return;
    }
    if (!canSubmit) {
      Taro.showToast({ title: serviceType === 'exchange' ? '请确认换货数量' : '请填写正确的申请金额', icon: 'none' });
      return;
    }

    try {
      setSubmitting(true);
      const result = await createAfterSale({
        orderId,
        orderItemId,
        type: serviceType,
        reason: reason.trim(),
        description: description.trim(),
        applyAmount: submitAmount,
        quantity,
        images,
      });
      const detail = result?.id ? result : result?.data;
      Taro.showToast({ title: '售后申请已提交', icon: 'success' });
      setTimeout(() => {
        if (detail?.id) {
          Taro.redirectTo({ url: `/pages/order/after-service-detail/index?id=${detail.id}` });
          return;
        }
        Taro.redirectTo({ url: '/pages/order/after-service-list/index' });
      }, 500);
    }
    catch (error: any) {
      Taro.showToast({ title: error?.msg || '提交失败', icon: 'none' });
    }
    finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return <View className="apply-service apply-service--loading"><Text>加载中...</Text></View>;
  }

  if (!eligibility?.canApply) {
    return (
      <View className="apply-service apply-service--empty">
        <Text className="apply-service__empty-title">暂不支持申请售后</Text>
        <Text className="apply-service__empty-desc">请更换商品或联系商家客服处理</Text>
      </View>
    );
  }

  return (
    <View className="apply-service">
      <View className="apply-service__card">
        <Text className="apply-service__title">申请售后</Text>
        <Text className="apply-service__subtitle">订单号：{orderNo || '--'}</Text>
        {!!productName && <Text className="apply-service__product">{productName}{skuName ? ` / ${skuName}` : ''}</Text>}
      </View>

      <View className="apply-service__card apply-service__tip-card">
        <Text className="apply-service__section-title">申请提示</Text>
        <Text className="apply-service__tip-text">1. 提交后商家会尽快审核您的申请，请耐心等待。</Text>
        <Text className="apply-service__tip-text">2. 当前最高可退金额为 {formatAmount(eligibility.maxAmount)}，可申请数量为 {eligibility.maxQuantity}。</Text>
        <Text className="apply-service__tip-text">3. 支持上传9张凭证图片，便于商家更快定位问题。</Text>
      </View>

      <View className="apply-service__card">
        <Text className="apply-service__section-title">售后类型</Text>
        <View className="apply-service__type-list">
          {eligibility.types.map((item) => (
            <View
              key={item}
              className={`apply-service__type-item ${serviceType === item ? 'apply-service__type-item--active' : ''}`}
              onClick={() => handleServiceTypeChange(item)}
            >
              <Text>{AFTER_SALE_TYPE_TEXT_MAP[item]}</Text>
            </View>
          ))}
        </View>
        <Text className="apply-service__type-hint">{typeHint}</Text>
      </View>

      <View className="apply-service__card">
        <Text className="apply-service__section-title">{'售后信息'}</Text>
        <View className="apply-service__row"><Text>{'可退金额'}</Text><Text>{formatAmount(eligibility.maxAmount)}</Text></View>
        <View className="apply-service__row"><Text>{'可售后数量'}</Text><Text>{eligibility.maxQuantity}</Text></View>
        <View className="apply-service__row"><Text>{'选择类型'}</Text><Text>{AFTER_SALE_TYPE_TEXT_MAP[serviceType]}</Text></View>

        <View className="apply-service__field">
          <View className="apply-service__field-head">
            <Text className="apply-service__field-label">{'申请数量'}</Text>
            <Text className="apply-service__field-tip">{'最多 '}{eligibility.maxQuantity}{' 件'}</Text>
          </View>
          <View className="apply-service__stepper">
            <View className={`apply-service__stepper-btn ${quantity <= 1 ? 'apply-service__stepper-btn--disabled' : ''}`} onClick={() => handleQuantityChange(quantity - 1)}><Text>-</Text></View>
            <View className="apply-service__stepper-value"><Text>{quantity}</Text></View>
            <View className={`apply-service__stepper-btn ${quantity >= eligibility.maxQuantity ? 'apply-service__stepper-btn--disabled' : ''}`} onClick={() => handleQuantityChange(quantity + 1)}><Text>+</Text></View>
          </View>
        </View>

        <View className="apply-service__field">
          <View className="apply-service__field-head">
            <Text className="apply-service__field-label">{'申请金额'}</Text>
            <Text className="apply-service__field-tip">{'本数量最高可退 '}{formatAmount(maxAmountByQuantity)}</Text>
          </View>
          <View className={`apply-service__amount-box ${serviceType === 'exchange' ? 'apply-service__amount-box--disabled' : ''}`}>
            <Text className="apply-service__amount-symbol">{'¥'}</Text>
            <Input
              className="apply-service__amount-input"
              type="digit"
              value={serviceType === 'exchange' ? '0.00' : amountInput}
              disabled={serviceType === 'exchange'}
              placeholder={centsToYuanInput(maxAmountByQuantity)}
              onInput={(event) => handleAmountInput(event.detail.value)}
              onBlur={handleAmountBlur}
            />
          </View>
        </View>
      </View>

      <View className="apply-service__card">
        <Text className="apply-service__section-title">售后原因</Text>
        <View className="apply-service__reason-list">
          {DEFAULT_REASONS.map((item) => (
            <View
              key={item}
              className={`apply-service__reason-item ${reason === item ? 'apply-service__reason-item--active' : ''}`}
              onClick={() => setReason(item)}
            >
              <Text>{item}</Text>
            </View>
          ))}
        </View>
      </View>

      <View className="apply-service__card">
        <Text className="apply-service__section-title">凭证图片</Text>
        <View className="apply-service__upload-grid">
          {images.map((item, index) => (
            <View key={`${item}-${index}`} className="apply-service__upload-item">
              <Image className="apply-service__upload-image" src={item} mode="aspectFill" onClick={() => handlePreviewImage(item)} />
              <View className="apply-service__upload-remove" onClick={() => handleRemoveImage(index)}>
                <Text className="apply-service__upload-remove-text">×</Text>
              </View>
            </View>
          ))}
          {images.length < 9 && (
            <View className="apply-service__upload-add" onClick={handleChooseImage}>
              <Text className="apply-service__upload-add-icon">+</Text>
              <Text className="apply-service__upload-add-text">{uploading ? '上传中...' : '添加图片'}</Text>
            </View>
          )}
        </View>
      </View>

      <View className="apply-service__card">
        <Text className="apply-service__section-title">问题描述</Text>
        <Textarea
          className="apply-service__textarea"
          maxlength={200}
          value={description}
          placeholder="请补充问题说明，方便商家更快处理"
          onInput={(event) => setDescription(event.detail.value)}
        />
      </View>

      <View className={`apply-service__submit ${canSubmit ? '' : 'apply-service__submit--disabled'}`} onClick={handleSubmit}>
        <Text>{submitting ? '提交中...' : '提交售后申请'}</Text>
      </View>
    </View>
  );
}
