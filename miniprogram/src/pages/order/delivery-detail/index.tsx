import { View, Text } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect } from 'react';
import PageNav from '../../../components/page-nav';
import './index.scss';
import { fetchOrderLogistics } from '../../../services/order/logistics';

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
  const [failed, setFailed] = useState(false);
  const [retry, setRetry] = useState(0);

  useEffect(() => {
    const params = router.params;
    let disposed = false;
    setLoading(true);
    setFailed(false);
    setNodes([]);
    setCompanyName('');
    setLogisticsNo('');

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

    const orderNo = params.orderNo || '';
    if (!orderNo) {
      setLoading(false);
      return;
    }
    fetchOrderLogistics(orderNo)
      .then((res: any) => {
        if (disposed) return;
        const data = res?.data || res || {};
        const traces = Array.isArray(data.traces) ? data.traces : [];
        setCompanyName(data.companyName || data.company || data.companyCode || '');
        setLogisticsNo(data.trackingNo || data.logisticsNo || '');
        setNodes(traces.map((trace: any, index: number) => ({
          title: trace.status || '',
          time: trace.time || '',
          desc: trace.context || trace.desc || '',
          active: index === 0,
        })));
      })
      .catch(() => { if (!disposed) setFailed(true); })
      .finally(() => { if (!disposed) setLoading(false); });
    return () => { disposed = true; };
  }, [router.params.orderNo, router.params.data, retry]);

  const handleCopyNo = () => {
    if (logisticsNo) {
      Taro.setClipboardData({ data: logisticsNo });
    }
  };

  if (loading) {
    return (
      <View className="delivery-detail"><PageNav title="物流详情" />
        <View className="order-state"><Text>加载中...</Text></View>
      </View>
    );
  }

  return (
    <View className="delivery-detail">
      <PageNav title="物流详情" />
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
          {nodes.length === 0 && <View className="order-state"><Text>{failed ? '物流加载失败' : '暂无物流轨迹，请稍后再查看'}</Text>{failed && <View className="order-state__action" onClick={() => setRetry((value) => value + 1)}>重新加载</View>}</View>}
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
                <Text className="delivery-node__desc">{node.desc || node.title}</Text>
                <Text className="delivery-node__time">{node.time}</Text>
              </View>
            </View>
          ))}
        </View>
      </View>
    </View>
  );
}
