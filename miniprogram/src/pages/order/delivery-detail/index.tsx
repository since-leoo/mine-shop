import { View, Text } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect } from 'react';
import './index.scss';

interface TrackingNode {
  title: string;
  time: string;
  desc: string;
  active?: boolean;
}

export default function DeliveryDetail() {
  const router = useRouter();
  const [companyName, setCompanyName] = useState('');
  const [logisticsNo, setLogisticsNo] = useState('');
  const [nodes, setNodes] = useState<TrackingNode[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const params = router.params;

    // Try to parse data from params (passed from order-detail)
    if (params.data) {
      try {
        const data = JSON.parse(decodeURIComponent(params.data));
        setCompanyName(data.company || '');
        setLogisticsNo(data.logisticsNo || '');
        setNodes(
          (data.nodes || []).map((n: any, i: number) => ({
            title: n.title || n.desc || '',
            time: n.date || n.time || '',
            desc: n.desc || '',
            active: i === 0,
          })),
        );
        setLoading(false);
        return;
      } catch (e) {}
    }

    // Mock data for standalone access
    setTimeout(() => {
      setCompanyName('顺丰速运');
      setLogisticsNo(params.orderNo || 'SF1234567890');
      setNodes([
        { title: '已签收', time: '2024-01-03 14:30', desc: '您的快递已签收', active: true },
        { title: '派送中', time: '2024-01-03 08:00', desc: '快递员正在派送', active: false },
        { title: '已到达', time: '2024-01-02 20:00', desc: '快递已到达您所在城市', active: false },
        { title: '运输中', time: '2024-01-01 16:00', desc: '快递正在运输中', active: false },
        { title: '已揽收', time: '2024-01-01 10:00', desc: '快递员已揽收', active: false },
      ]);
      setLoading(false);
    }, 500);
  }, [router.params]);

  const handleCopyNo = () => {
    if (logisticsNo) {
      Taro.setClipboardData({ data: logisticsNo });
    }
  };

  if (loading) {
    return (
      <View className="delivery-detail delivery-detail--loading">
        <Text className="delivery-detail__loading-text">加载中...</Text>
      </View>
    );
  }

  return (
    <View className="delivery-detail">
      {/* Express info */}
      <View className="delivery-detail__info-card">
        <View className="delivery-detail__info-row">
          <Text className="delivery-detail__info-label">快递公司</Text>
          <Text className="delivery-detail__info-value">{companyName || '-'}</Text>
        </View>
        <View className="delivery-detail__info-row" onClick={handleCopyNo}>
          <Text className="delivery-detail__info-label">快递单号</Text>
          <View className="delivery-detail__info-value-row">
            <Text className="delivery-detail__info-value">{logisticsNo || '-'}</Text>
            {logisticsNo && <Text className="delivery-detail__copy-btn">复制</Text>}
          </View>
        </View>
      </View>

      {/* Timeline */}
      <View className="delivery-detail__timeline-card">
        <Text className="delivery-detail__card-title">物流跟踪</Text>
        <View className="delivery-detail__timeline">
          {nodes.map((node, index) => (
            <View
              className={`delivery-node ${node.active ? 'delivery-node--active' : ''}`}
              key={index}
            >
              <View className="delivery-node__dot-col">
                <View className={`delivery-node__dot ${node.active ? 'delivery-node__dot--active' : ''}`} />
                {index < nodes.length - 1 && <View className="delivery-node__line" />}
              </View>
              <View className="delivery-node__content">
                <Text className="delivery-node__desc">{node.desc}</Text>
                <Text className="delivery-node__time">{node.time}</Text>
              </View>
            </View>
          ))}
        </View>
      </View>
    </View>
  );
}
