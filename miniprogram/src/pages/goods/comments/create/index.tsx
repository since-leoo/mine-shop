import { View, Text, Image, Textarea } from '@tarojs/components';
import Taro from '@tarojs/taro';
import { useState, useEffect, useCallback, useRef } from 'react';
import './index.scss';

function decodeParam(value?: string) {
  if (!value) {
    return '';
  }

  try {
    return decodeURIComponent(value);
  } catch {
    return value;
  }
}

interface CommentRateProps {
  value: number;
  onChange: (value: number) => void;
}

function CommentRate({ value, onChange }: CommentRateProps) {
  return (
    <View className="comment-rate">
      {Array.from({ length: 5 }, (_, index) => {
        const nextValue = index + 1;
        const active = nextValue <= value;

        return (
          <Text
            key={nextValue}
            className={`comment-rate__star ${active ? 'comment-rate__star--active' : ''}`}
            style={{
              color: active ? '#f3b63f' : '#d8c6b6',
              fontSize: '46rpx',
              textShadow: active ? '0 4rpx 12rpx rgba(243, 182, 63, 0.26)' : '0 2rpx 6rpx rgba(120, 80, 28, 0.08)',
              transform: active ? 'scale(1.06)' : 'scale(1)',
            }}
            onClick={() => onChange(nextValue)}
          >
            {active ? '★' : '☆'}
          </Text>
        );
      })}
    </View>
  );
}

export default function CreateCommentPage() {
  const [goodRateValue, setGoodRateValue] = useState(5);
  const [conveyRateValue, setConveyRateValue] = useState(5);
  const [serviceRateValue, setServiceRateValue] = useState(5);
  const [isAnonymous, setIsAnonymous] = useState(false);
  const [uploadFiles, setUploadFiles] = useState<string[]>([]);
  const [commentText, setCommentText] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const [imgUrl, setImgUrl] = useState('');
  const [title, setTitle] = useState('');
  const [goodsDetail, setGoodsDetail] = useState('');

  const orderIdRef = useRef<number | null>(null);
  const orderItemIdRef = useRef<number | null>(null);

  useEffect(() => {
    const instance = Taro.getCurrentInstance();
    const params = instance.router?.params || {};
    orderIdRef.current = params.orderId ? Number(params.orderId) : null;
    orderItemIdRef.current = params.orderItemId ? Number(params.orderItemId) : null;
    setImgUrl(decodeParam(params.productImage || params.imgUrl || ''));
    setTitle(decodeParam(params.productName || params.title || ''));
    setGoodsDetail(decodeParam(params.skuName || params.specs || ''));
  }, []);

  const handleChooseImage = useCallback(() => {
    const remaining = 9 - uploadFiles.length;
    if (remaining <= 0) {
      Taro.showToast({ title: '最多上传9张图片', icon: 'none' });
      return;
    }

    Taro.chooseImage({
      count: remaining,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        setUploadFiles((prev) => [...prev, ...res.tempFilePaths]);
      },
    });
  }, [uploadFiles.length]);

  const handleRemoveImage = useCallback((index: number) => {
    setUploadFiles((prev) => prev.filter((_, i) => i !== index));
  }, []);

  const isAllowedSubmit = goodRateValue > 0 && commentText.trim().length > 0;

  const handleSubmit = useCallback(async () => {
    if (!isAllowedSubmit || submitting) return;

    setSubmitting(true);
    try {
      const { request } = require('../../../../services/request');
      await request({
        url: '/api/v1/review',
        method: 'POST',
        data: {
          rating: goodRateValue,
          content: commentText,
          images: uploadFiles,
          isAnonymous,
          orderId: orderIdRef.current,
          orderItemId: orderItemIdRef.current,
          logisticsRating: conveyRateValue,
          serviceRating: serviceRateValue,
        },
        needAuth: true,
      });
      Taro.showToast({ title: '评价提交成功', icon: 'success' });
      setTimeout(() => {
        Taro.navigateBack();
      }, 1500);
    } catch (err: any) {
      Taro.showToast({ title: err.msg || '评价提交失败，请重试', icon: 'none' });
    } finally {
      setSubmitting(false);
    }
  }, [isAllowedSubmit, submitting, goodRateValue, commentText, uploadFiles, isAnonymous, conveyRateValue, serviceRateValue]);

  return (
    <View className="create-comment-page">
      <View className="comment-form-card">
        <View className="goods-info-row">
          {imgUrl && (
            <Image className="goods-info-row__img" src={imgUrl} mode="aspectFill" />
          )}
          <View className="goods-info-row__text">
            <Text className="goods-info-row__title">{title}</Text>
            {goodsDetail && (
              <Text className="goods-info-row__detail">{goodsDetail}</Text>
            )}
          </View>
        </View>

        <View className="rate-row">
          <Text className="rate-row__label">商品评价</Text>
          <CommentRate value={goodRateValue} onChange={setGoodRateValue} />
        </View>

        <View className="textarea-wrap">
          <Textarea
            className="textarea-input"
            placeholder="对商品满意吗？评论一下"
            maxlength={500}
            value={commentText}
            onInput={(e) => setCommentText(e.detail.value)}
          />
          <Text className="textarea-counter">{commentText.length}/500</Text>
        </View>

        <View className="upload-area">
          <View className="upload-area__grid">
            {uploadFiles.map((file, idx) => (
              <View key={idx} className="upload-area__item">
                <Image className="upload-area__img" src={file} mode="aspectFill" />
                <View
                  className="upload-area__remove"
                  onClick={() => handleRemoveImage(idx)}
                >
                  <Text className="upload-area__remove-icon">{'×'}</Text>
                </View>
              </View>
            ))}
            {uploadFiles.length < 9 && (
              <View className="upload-area__add" onClick={handleChooseImage}>
                <Text className="upload-area__add-icon">+</Text>
                <Text className="upload-area__add-text">添加图片</Text>
              </View>
            )}
          </View>
        </View>

        <View className="anonymous-row" onClick={() => setIsAnonymous((prev) => !prev)}>
          <View className={`anonymous-check ${isAnonymous ? 'anonymous-check--active' : ''}`}>
            <Text className="anonymous-check__icon">{isAnonymous ? '√' : ''}</Text>
          </View>
          <Text className="anonymous-row__text">匿名评价</Text>
        </View>
      </View>

      <View className="comment-form-card comment-form-card--service">
        <Text className="service-title">物流服务评价</Text>
        <View className="rate-row">
          <Text className="rate-row__label">物流评价</Text>
          <CommentRate value={conveyRateValue} onChange={setConveyRateValue} />
        </View>
        <View className="rate-row">
          <Text className="rate-row__label">服务评价</Text>
          <CommentRate value={serviceRateValue} onChange={setServiceRateValue} />
        </View>
      </View>

      <View className="submit-placeholder" />

      <View className="submit-bar">
        <View
          className={`submit-btn ${isAllowedSubmit ? '' : 'submit-btn--disabled'}`}
          onClick={handleSubmit}
        >
          <Text className="submit-btn__text">
            {submitting ? '提交中...' : '提交'}
          </Text>
        </View>
      </View>
    </View>
  );
}
