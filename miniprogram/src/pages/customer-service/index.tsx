import { Button, Input, ScrollView, Text, View } from '@tarojs/components';
import Taro, { useDidHide, useDidShow } from '@tarojs/taro';
import { useRef, useState } from 'react';
import { closeCustomerConversation, createClientMessageId, customerSocketUrl, getCustomerMessages, getCustomerServiceConfig, getCustomerSocketTicket, openCustomerConversation } from '../../services/customer-service';
import './index.scss';

type Message = { id?: number; messageType?: string; contentJson?: { text?: string; url?: string; title?: string; answer?: string }; senderType?: string };

export default function CustomerServicePage() {
  const [conversation, setConversation] = useState<any>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [text, setText] = useState('');
  const [status, setStatus] = useState('连接中');
  const socketRef = useRef<any>(null);

  const connect = async () => {
    try {
      const [ticket, serviceConfig] = await Promise.all([getCustomerSocketTicket(), getCustomerServiceConfig()]);
      if (!serviceConfig.enabled) throw new Error(serviceConfig.offlineMessage || '客服暂未开启');
      const socket = Taro.connectSocket({ url: customerSocketUrl(serviceConfig.gatewayUrl, serviceConfig.socketPath) });
      socketRef.current = socket;
      socket.onOpen(() => { setStatus('在线'); socket.send({ data: JSON.stringify({ event: 'auth', payload: { ticket: ticket.ticket || ticket } }) }); });
      socket.onMessage((event) => {
        const data = JSON.parse(event.data as string);
        if (data.event === 'message:created') setMessages((old) => [...old, data.payload]);
        if (data.event === 'system:error') Taro.showToast({ title: data.payload?.message || '客服连接异常', icon: 'none' });
      });
      socket.onClose(() => setStatus('已断开'));
      const opened = await openCustomerConversation();
      setConversation(opened);
      const history = await getCustomerMessages(opened.conversationNo);
      setMessages(history || []);
    } catch (error: any) { setStatus('连接失败'); Taro.showToast({ title: error?.msg || '客服暂不可用', icon: 'none' }); }
  };

  useDidShow(() => { void connect(); });
  useDidHide(() => { socketRef.current?.close({}); });

  const send = () => {
    const value = text.trim();
    if (!value || !conversation || !socketRef.current) return;
    socketRef.current.send({ data: JSON.stringify({ event: 'message:send', payload: { message_type: 'text', conversation_no: conversation.conversationNo, client_message_id: createClientMessageId(), text: value } }) });
    setText('');
  };

  return <View className="customer-service"><View className="customer-service__header"><Text>在线客服</Text><Text>{status}</Text></View><ScrollView scrollY className="customer-service__messages">{messages.map((message, index) => <View key={message.id || index} className={`message message--${message.senderType === 'member' ? 'mine' : 'agent'}`}><Text>{message.contentJson?.text || message.contentJson?.answer || message.contentJson?.title || (message.messageType === 'image' ? '[图片]' : '')}</Text></View>)}</ScrollView><View className="customer-service__composer"><Input value={text} onInput={(event) => setText(event.detail.value)} placeholder="输入咨询内容" /><Button onClick={send}>发送</Button><Button onClick={() => conversation && closeCustomerConversation(conversation.conversationNo)}>结束</Button></View></View>;
}
