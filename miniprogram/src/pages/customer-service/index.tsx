import { Button, Image, Input, ScrollView, Text, View } from '@tarojs/components';
import Taro, { useDidHide, useDidShow } from '@tarojs/taro';
import { useMemo, useRef, useState } from 'react';
import { uploadImage } from '../../services/upload';
import {
  closeCustomerConversation,
  createClientMessageId,
  customerSocketUrl,
  getCustomerMessages,
  getCustomerServiceConfig,
  getCustomerSocketTicket,
  openCustomerConversation,
} from '../../services/customer-service';
import './index.scss';

type MessageContent = {
  text?: string;
  url?: string;
  title?: string;
  answer?: string;
};

type Message = {
  id?: number;
  clientMessageId?: string;
  client_message_id?: string;
  messageType?: string;
  message_type?: string;
  contentJson?: MessageContent;
  content_json?: MessageContent;
  senderType?: string;
  sender_type?: string;
  sentAt?: string;
  sent_at?: string;
};

type ServiceConfig = {
  enabled?: boolean;
  gatewayUrl?: string;
  gateway_url?: string;
  socketPath?: string;
  socket_path?: string;
  allowMemberImage?: boolean;
  allow_member_image?: boolean;
  maxImageSizeMb?: number;
  max_image_size_mb?: number;
  welcomeMessage?: string;
  welcome_message?: string;
  offlineMessage?: string;
  offline_message?: string;
};

function messageTypeOf(message: Message): string {
  return message.messageType || message.message_type || 'text';
}

function contentOf(message: Message): MessageContent {
  return message.contentJson || message.content_json || {};
}

function isMyMessage(message: Message): boolean {
  return (message.senderType || message.sender_type) === 'member';
}

function formatTime(message: Message): string {
  const value = message.sentAt || message.sent_at;
  if (!value) return '';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

export default function CustomerServicePage() {
  const [conversation, setConversation] = useState<any>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [text, setText] = useState('');
  const [status, setStatus] = useState('正在连接');
  const [serviceConfig, setServiceConfig] = useState<ServiceConfig>({});
  const [moreVisible, setMoreVisible] = useState(false);
  const [uploading, setUploading] = useState(false);
  const socketRef = useRef<any>(null);
  const refreshTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const imageEnabled = serviceConfig.allowMemberImage ?? serviceConfig.allow_member_image ?? true;
  const welcomeMessage = serviceConfig.welcomeMessage || serviceConfig.welcome_message || '您好，请问有什么可以帮助您？';
  const canSendText = text.trim().length > 0 && conversation && !uploading;
  const statusLabel = useMemo(() => status === '在线' ? '客服在线' : status, [status]);

  const appendMessage = (message: Message) => {
    const clientMessageId = message.clientMessageId || message.client_message_id;
    setMessages((previous) => {
      if (message.id && previous.some((item) => item.id === message.id)) return previous;
      if (clientMessageId && previous.some((item) => (item.clientMessageId || item.client_message_id) === clientMessageId)) return previous;
      return [...previous, message];
    });
  };

  const connect = async () => {
    try {
      socketRef.current?.close({});
      setStatus('正在连接');
      const [ticket, nextConfig] = await Promise.all([getCustomerSocketTicket(), getCustomerServiceConfig()]);
      if (!nextConfig.enabled) throw new Error(nextConfig.offlineMessage || nextConfig.offline_message || '客服暂未开启');

      setServiceConfig(nextConfig);
      const socket = await Taro.connectSocket({
        url: customerSocketUrl(nextConfig.gatewayUrl || nextConfig.gateway_url, nextConfig.socketPath || nextConfig.socket_path),
      });
      socketRef.current = socket;
      socket.onOpen(() => {
        setStatus('在线');
        socket.send({ data: JSON.stringify({ event: 'auth', payload: { ticket: ticket.ticket || ticket } }) });
      });
      socket.onMessage((event) => {
        const data = JSON.parse(event.data as string);
        if (data.event === 'message:created') appendMessage(data.payload || {});
        if (data.event === 'system:error') Taro.showToast({ title: data.payload?.message || '客服连接异常', icon: 'none' });
        if (data.event === 'conversation:closed') setStatus('会话已结束');
      });
      socket.onClose(() => setStatus((current) => current === '会话已结束' ? current : '连接已断开'));

      const opened = await openCustomerConversation();
      setConversation(opened);
      const history = await getCustomerMessages(opened.conversationNo);
      setMessages(history || []);
      if (refreshTimerRef.current) clearInterval(refreshTimerRef.current);
      refreshTimerRef.current = setInterval(() => {
        void getCustomerMessages(opened.conversationNo).then((latest) => {
          setMessages((previous) => {
            const merged = [...previous];
            for (const message of latest || []) {
              const clientId = message.clientMessageId || message.client_message_id;
              const exists = (message.id && merged.some((item) => item.id === message.id))
                || (clientId && merged.some((item) => (item.clientMessageId || item.client_message_id) === clientId));
              if (!exists) merged.push(message);
            }
            return merged.sort((left, right) => (left.id || 0) - (right.id || 0));
          });
        }).catch(() => undefined);
      }, 3000);
    }
    catch (error: any) {
      setStatus('暂不可用');
      Taro.showToast({ title: error?.msg || error?.message || '客服暂不可用', icon: 'none' });
    }
  };

  useDidShow(() => { void connect(); });
  useDidHide(() => {
    socketRef.current?.close({});
    socketRef.current = null;
    if (refreshTimerRef.current) clearInterval(refreshTimerRef.current);
    refreshTimerRef.current = null;
  });

  const sendPayload = (payload: Record<string, unknown>) => {
    if (!conversation || !socketRef.current) {
      Taro.showToast({ title: '客服连接未就绪', icon: 'none' });
      return false;
    }

    socketRef.current.send({
      data: JSON.stringify({
        event: 'message:send',
        payload: {
          conversation_no: conversation.conversationNo,
          client_message_id: createClientMessageId(),
          ...payload,
        },
      }),
    });
    return true;
  };

  const sendText = () => {
    const value = text.trim();
    if (!value || !sendPayload({ message_type: 'text', text: value })) return;
    setText('');
  };

  const chooseAndSendImage = async (sourceType: Array<'album' | 'camera'>) => {
    if (!imageEnabled) {
      Taro.showToast({ title: '当前客服暂不支持图片消息', icon: 'none' });
      return;
    }

    try {
      const result = await Taro.chooseImage({ count: 1, sizeType: ['compressed'], sourceType });
      const filePath = result.tempFilePaths?.[0];
      if (!filePath) return;

      setUploading(true);
      Taro.showLoading({ title: '图片发送中' });
      const url = await uploadImage(filePath);
      if (sendPayload({ message_type: 'image', url })) setMoreVisible(false);
    }
    catch (error: any) {
      Taro.showToast({ title: error?.msg || '图片发送失败', icon: 'none' });
    }
    finally {
      Taro.hideLoading();
      setUploading(false);
    }
  };

  const endConversation = async () => {
    if (!conversation) return;
    try {
      await closeCustomerConversation(conversation.conversationNo);
      socketRef.current?.close({});
      setStatus('会话已结束');
      setMoreVisible(false);
      Taro.showToast({ title: '会话已结束', icon: 'none' });
    }
    catch (error: any) {
      Taro.showToast({ title: error?.msg || '结束会话失败', icon: 'none' });
    }
  };

  return (
    <View className="customer-service">
      <View className="customer-service__header">
        <View className="customer-service__agent">
          <View className="customer-service__agent-avatar"><Text>客</Text></View>
          <View>
            <Text className="customer-service__title">在线客服</Text>
            <View className="customer-service__status"><Text className={`customer-service__status-dot ${status === '在线' ? 'is-online' : ''}`} /><Text>{statusLabel}</Text></View>
          </View>
        </View>
        <View className="customer-service__end" onClick={endConversation}><Text>结束咨询</Text></View>
      </View>

      <ScrollView scrollY className="customer-service__messages">
        <View className="customer-service__notice"><Text>请勿发送银行卡、验证码等敏感信息</Text></View>
        <View className="customer-service__welcome">
          <View className="customer-service__welcome-avatar"><Text>客</Text></View>
          <View className="customer-service__welcome-bubble"><Text>{welcomeMessage}</Text></View>
        </View>
        {messages.map((message, index) => {
          const mine = isMyMessage(message);
          const content = contentOf(message);
          const type = messageTypeOf(message);
          return (
            <View key={message.id || message.clientMessageId || message.client_message_id || index} className={`customer-service__message ${mine ? 'is-mine' : ''}`}>
              {!mine && <View className="customer-service__message-avatar"><Text>客</Text></View>}
              <View className="customer-service__message-body">
                {type === 'image' && content.url
                  ? <Image className="customer-service__message-image" src={content.url} mode="widthFix" onClick={() => Taro.previewImage({ current: content.url, urls: [content.url!] })} />
                  : <View className="customer-service__bubble"><Text>{content.text || content.answer || content.title || '[暂不支持的消息]'}</Text></View>}
                {formatTime(message) && <Text className="customer-service__message-time">{formatTime(message)}</Text>}
              </View>
              {mine && <View className="customer-service__message-avatar customer-service__message-avatar--mine"><Text>我</Text></View>}
            </View>
          );
        })}
      </ScrollView>

      {moreVisible && (
        <View className="customer-service__more-panel">
          <View className="customer-service__more-action" onClick={() => void chooseAndSendImage(['album'])}>
            <View className="customer-service__more-icon"><Text>图</Text></View><Text>相册</Text>
          </View>
          <View className="customer-service__more-action" onClick={() => void chooseAndSendImage(['camera'])}>
            <View className="customer-service__more-icon"><Text>拍</Text></View><Text>拍照</Text>
          </View>
        </View>
      )}

      <View className="customer-service__composer">
        <View className={`customer-service__add ${moreVisible ? 'is-active' : ''}`} onClick={() => setMoreVisible((visible) => !visible)}><Text>＋</Text></View>
        <Input className="customer-service__input" value={text} maxlength={1000} adjustPosition placeholder="输入消息…" onInput={(event) => setText(event.detail.value)} confirmType="send" onConfirm={sendText} />
        <Button className={`customer-service__send ${canSendText ? 'is-ready' : ''}`} disabled={!canSendText} onClick={sendText}>发送</Button>
      </View>
    </View>
  );
}
