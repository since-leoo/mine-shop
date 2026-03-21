import { View, Text, Image } from '@tarojs/components';
import Taro, { useReachBottom } from '@tarojs/taro';
import { useState, useEffect, useCallback, useRef } from 'react';
import { fetchComments } from '../../../services/comments/fetchComments';
import { fetchCommentsCount } from '../../../services/comments/fetchCommentsCount';
import LoadMore from '../../../components/LoadMore';
import './index.scss';

interface CommentItem {
  commentScore: number;
  userName: string;
  commentContent: string;
  commentResources: any[];
  commentTime: string;
  userHeadUrl: string;
  specInfo: string;
  isAnonymity: boolean;
  sellerReply: string;
}

interface CountObj {
  badCount: string;
  commentCount: string;
  goodCount: string;
  middleCount: string;
  hasImageCount: string;
  uidCount: string;
}

type TabKey = '' | '3' | '2' | '1' | '4';

const TABS: { key: TabKey; label: string; countKey: keyof CountObj }[] = [
  { key: '', label: '全部', countKey: 'commentCount' },
  { key: '4', label: '有图', countKey: 'hasImageCount' },
  { key: '3', label: '好评', countKey: 'goodCount' },
  { key: '2', label: '中评', countKey: 'middleCount' },
  { key: '1', label: '差评', countKey: 'badCount' },
];

export default function CommentsPage() {
  const [commentList, setCommentList] = useState<CommentItem[]>([]);
  const [loadMoreStatus, setLoadMoreStatus] = useState<0 | 1 | 2 | 3>(0);
  const [activeTab, setActiveTab] = useState<TabKey>('');
  const [countObj, setCountObj] = useState<CountObj>({
    badCount: '0',
    commentCount: '0',
    goodCount: '0',
    middleCount: '0',
    hasImageCount: '0',
    uidCount: '0',
  });

  const spuIdRef = useRef('');
  const pageNumRef = useRef(1);
  const totalCountRef = useRef(0);

  // Format time
  const formatTime = (ts: string | number) => {
    try {
      const d = new Date(Number(ts));
      const pad = (n: number) => String(n).padStart(2, '0');
      return `${d.getFullYear()}/${pad(d.getMonth() + 1)}/${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    } catch {
      return String(ts);
    }
  };

  const loadComments = useCallback(async (reset = true, tabKey?: TabKey) => {
    const currentTab = tabKey ?? activeTab;
    if (loadMoreStatus === 1 && !reset) return;
    setLoadMoreStatus(1);

    const params: any = {
      pageNum: reset ? 1 : pageNumRef.current + 1,
      pageSize: 30,
      queryParameter: {
        spuId: spuIdRef.current,
      },
    };

    // Tab filtering
    if (currentTab === '4') {
      params.queryParameter.hasImage = true;
    } else if (['3', '2', '1'].includes(currentTab)) {
      params.queryParameter.commentLevel = Number(currentTab);
    }

    try {
      const data = await fetchComments(params);
      const { pageList = [], totalCount = 0 } = data || {};
      const totalNum = Number(totalCount || 0);

      const formattedList = pageList.map((item: any) => ({
        ...item,
        commentTime: formatTime(item.commentTime),
      }));

      if (totalNum === 0 && reset) {
        setCommentList([]);
        totalCountRef.current = 0;
        setLoadMoreStatus(2);
        return;
      }

      const newList = reset ? formattedList : [...commentList, ...formattedList];
      pageNumRef.current = reset ? 1 : pageNumRef.current + 1;
      totalCountRef.current = totalNum;
      setCommentList(newList);
      setLoadMoreStatus(newList.length >= totalNum ? 2 : 0);
    } catch {
      if (reset) {
        setCommentList([]);
        totalCountRef.current = 0;
      }
      setLoadMoreStatus(3);
    }
  }, [activeTab, commentList, loadMoreStatus]);

  const loadCount = useCallback(async () => {
    try {
      const result = await fetchCommentsCount({ spuId: spuIdRef.current });
      setCountObj({
        badCount: String(result?.badCount || '0'),
        commentCount: String(result?.commentCount || '0'),
        goodCount: String(result?.goodCount || '0'),
        middleCount: String(result?.middleCount || '0'),
        hasImageCount: String(result?.hasImageCount || '0'),
        uidCount: String(result?.uidCount || '0'),
      });
    } catch (error) {
      console.error('count error:', error);
      setCountObj({
        badCount: '0',
        commentCount: '0',
        goodCount: '0',
        middleCount: '0',
        hasImageCount: '0',
        uidCount: '0',
      });
    }
  }, []);

  useEffect(() => {
    const instance = Taro.getCurrentInstance();
    const params = instance.router?.params || {};
    spuIdRef.current = params.spuId || '';
    loadCount();
    loadComments(true);
  }, []);

  useReachBottom(() => {
    if (commentList.length < totalCountRef.current) {
      loadComments(false);
    } else if (commentList.length > 0) {
      setLoadMoreStatus(2);
    }
  });

  const handleTabChange = useCallback((tabKey: TabKey) => {
    if (tabKey === activeTab) return;
    setActiveTab(tabKey);
    setCommentList([]);
    pageNumRef.current = 1;
    setLoadMoreStatus(0);
    // Need to pass the new tabKey since state hasn't updated yet
    setTimeout(() => {
      loadComments(true, tabKey);
    }, 0);
  }, [activeTab, loadComments]);

  // Render stars
  const renderStars = (score: number) => {
    return Array.from({ length: 5 }, (_, i) => (
      <Text
        key={i}
        className={`comment-star ${i < score ? 'comment-star--active' : ''}`}
        style={{
          color: i < score ? '#f3b63f' : '#d8c6b6',
          fontSize: '30rpx',
          textShadow: i < score ? '0 4rpx 10rpx rgba(243, 182, 63, 0.22)' : '0 2rpx 6rpx rgba(120, 80, 28, 0.08)',
        }}>
        {i < score ? '★' : '☆'}
      </Text>
    ));
  };

  const handleRetry = useCallback(() => {
    loadComments(false);
  }, [loadComments]);

  return (
    <View className="comments-page">
      {/* Tab Bar */}
      <View className="comments-tabs">
        {TABS.map((tab) => (
          <View
            key={tab.key}
            className={`comments-tabs__item ${activeTab === tab.key ? 'comments-tabs__item--active' : ''}`}
            onClick={() => handleTabChange(tab.key)}
          >
            <Text className="comments-tabs__text">
              {tab.label}({countObj[tab.countKey] || '0'})
            </Text>
          </View>
        ))}
      </View>

      {/* Comment List */}
      <View className="comments-list">
        {commentList.map((item, idx) => (
          <View key={idx} className="comment-card">
            <View className="comment-card__header">
              <Image
                className="comment-card__avatar"
                src={item.userHeadUrl || ''}
              />
              <View className="comment-card__header-right">
                <Text className="comment-card__name">
                  {item.isAnonymity ? '匿名用户' : (item.userName || '用户')}
                </Text>
                <View className="comment-card__stars">
                  {renderStars(item.commentScore)}
                </View>
              </View>
              <Text className="comment-card__time">{item.commentTime}</Text>
            </View>

            <Text className="comment-card__content">
              {item.commentContent || '用户未填写评价'}
            </Text>

            {item.specInfo && (
              <Text className="comment-card__spec">{item.specInfo}</Text>
            )}

            {/* Comment Images */}
            {item.commentResources && item.commentResources.length > 0 && (
              <View className="comment-card__images">
                {item.commentResources.map((res: any, rIdx: number) => (
                  <Image
                    key={rIdx}
                    className="comment-card__image"
                    src={res.src || res.url || res}
                    mode="aspectFill"
                    onClick={() => {
                      const urls = item.commentResources.map((r: any) => r.src || r.url || r);
                      Taro.previewImage({ current: urls[rIdx], urls });
                    }}
                  />
                ))}
              </View>
            )}

            {/* Seller Reply */}
            {item.sellerReply && (
              <View className="comment-card__reply">
                <Text className="comment-card__reply-label">商家回复：</Text>
                <Text className="comment-card__reply-text">{item.sellerReply}</Text>
              </View>
            )}
          </View>
        ))}
      </View>

      {/* Empty */}
      {commentList.length === 0 && loadMoreStatus !== 1 && (
        <View className="empty-state">
          <Text className="empty-state__icon">{'💬'}</Text>
          <Text className="empty-state__text">暂无评价</Text>
        </View>
      )}

      <LoadMore
        status={loadMoreStatus}
        listIsEmpty={commentList.length === 0}
        onRetry={handleRetry}
      />
    </View>
  );
}
