import { Button, Image, Input, Picker, ScrollView, Text, View } from '@tarojs/components';
import Taro, { useDidHide, useDidShow } from '@tarojs/taro';
import { useEffect, useMemo, useRef, useState } from 'react';
import PageNav from '../../components/page-nav';
import { isH5 } from '../../common/platform';
import { isLoggedIn } from '../../common/auth';
import { redirectToLogin } from '../../common/auth-guard';
import { uploadImage } from '../../services/upload';
import { closeCustomerConversation, createClientMessageId, customerSocketUrl, getCustomerMessages, getCustomerServiceConfig, getCustomerSocketTicket, openCustomerConversation, getCustomerConversation, requestHuman, getCustomerCategories, classifyCustomerConversation, sendCustomerText, rateCustomerConversation } from './services';
import { CustomerServiceSession, clientIdOf, mergeMessages } from './session';
import type { SupportMessage } from './session';
import './index.scss';
const receptionLabels: Record<string, string> = { ai_active: 'AI 正在为您服务', human_waiting: '已转人工，正在排队', human_assigned: '等待客服接待', human_active: '人工客服接待中', human_deferred: '人工暂不可用，已保留您的问题', closed: '会话已结束' };
const contentOf = (message: SupportMessage) => message.contentJson || message.content_json || {};
const messageTypeOf = (message: SupportMessage) => message.messageType || message.message_type || 'text';
const senderOf = (message: SupportMessage) => message.senderType || message.sender_type;
const isMyMessage = (message: SupportMessage) => senderOf(message) === 'member';
function formatTime(message: SupportMessage) { const value = message.sentAt || message.sent_at; if (!value) return ''; const date = new Date(value); return Number.isNaN(date.getTime()) ? '' : `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`; }
function categoryPath(items: any[], item: any) { const names = [item.name]; const seen = new Set([item.id]); let parent = item.parentId || item.parent_id; while (parent && !seen.has(parent)) { seen.add(parent); const p = items.find(i => i.id === parent); if (!p) break; names.unshift(p.name); parent = p.parentId || p.parent_id; } return names.join(' / '); }
export default function CustomerServicePage() {
  const [conversation, setConversation] = useState<any>(null);
  const [messages, setMessages] = useState<SupportMessage[]>([]);
  const [text, setText] = useState('');
  const [status, setStatus] = useState('正在连接');
  const [serviceConfig, setServiceConfig] = useState<any>({});
  const [categories, setCategories] = useState<any[]>([]);
  const [moreVisible, setMoreVisible] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [transferring, setTransferring] = useState(false);
  const [rated, setRated] = useState(false);
  const [connectionError, setConnectionError] = useState('');
  const sessionRef = useRef<CustomerServiceSession>();
  const generationRef = useRef(0);
  const conversationNo = conversation?.conversationNo || conversation?.conversation_no;
  const reception = conversation?.receptionState || conversation?.reception_state || (conversation?.status === 'closed' ? 'closed' : 'human_waiting');
  const closed = reception === 'closed';
  const imageEnabled = serviceConfig.allowMemberImage ?? serviceConfig.allow_member_image ?? true;
  const welcomeMessage = serviceConfig.welcomeMessage || serviceConfig.welcome_message || '您好，请问有什么可以帮助您？';
  const canSendText = text.trim().length > 0 && !!conversationNo && !closed && !uploading;
  const statusLabel = useMemo(() => status === '在线' ? receptionLabels[reception] || '已连接' : status, [status, reception]);
  const connect = async () => {
    const generation = ++generationRef.current; sessionRef.current?.stop(); setConversation(null); setMessages([]); setCategories([]); setConnectionError(''); setStatus('正在连接');
    try {
      const nextConfig = await getCustomerServiceConfig(); if (generation !== generationRef.current) return;
      if (!nextConfig.enabled) throw new Error(nextConfig.offlineMessage || nextConfig.offline_message || '客服暂未开启');
      setServiceConfig(nextConfig);
      const opened = await openCustomerConversation(); if (generation !== generationRef.current) return;
      setConversation(opened); setMessages([]); setRated(false);
      const no = opened.conversationNo || opened.conversation_no;
      const session = new CustomerServiceSession(no, {
        ticket: getCustomerSocketTicket,
        socket: () => Taro.connectSocket({ url: customerSocketUrl(nextConfig.gatewayUrl || nextConfig.gateway_url, nextConfig.socketPath || nextConfig.socket_path) }) as any,
        history: (after, limit) => getCustomerMessages(no, after, limit), conversation: () => getCustomerConversation(no),
        sendText: (id, value) => sendCustomerText(no, id, value),
        messages: incoming => { if (generation === generationRef.current) setMessages(previous => mergeMessages(previous, incoming)); },
        status: value => { if (generation === generationRef.current) setStatus(value); },
        updateConversation: value => { if (generation === generationRef.current) setConversation(value); },
        error: title => { void Taro.showToast({ title, icon: 'none' }); },
      });
      sessionRef.current = session; session.start(); void session.recover().catch(() => undefined);
      void getCustomerCategories().then(items => { if (generation === generationRef.current) setCategories(items || []); }).catch(() => undefined);
    } catch (error: any) { if (generation !== generationRef.current) return; setStatus('暂不可用'); setConnectionError(error?.msg || error?.message || '客服暂不可用'); void Taro.showToast({ title: error?.msg || error?.message || '客服暂不可用', icon: 'none' }); }
  };
  useDidShow(() => {
    if (isH5() && !isLoggedIn()) { redirectToLogin('/pages/customer-service/index'); return; }
    void connect();
  });
  useEffect(() => () => { ++generationRef.current; sessionRef.current?.stop(); }, []);
  useDidHide(() => { ++generationRef.current; sessionRef.current?.stop(); sessionRef.current = undefined; });
  const sendPayload = (payload: Record<string, unknown>) => { if (!conversationNo || closed || !sessionRef.current) return false; void sessionRef.current.send(payload, createClientMessageId()); return true; };
  const sendText = () => { const value = text.trim(); if (value && sendPayload({ message_type: 'text', text: value })) setText(''); };
  const chooseAndSendImage = async (sourceType: Array<'album' | 'camera'>) => {
    if (!imageEnabled) { void Taro.showToast({ title: '当前客服暂不支持图片消息', icon: 'none' }); return; }
    try {
      const result = await Taro.chooseImage({ count: 1, sizeType: ['compressed'], sourceType }); const filePath = result.tempFilePaths?.[0]; if (!filePath) return;
      const size = result.tempFiles?.[0]?.size || 0; if (size > (serviceConfig.maxImageSizeMb || serviceConfig.max_image_size_mb || 5) * 1024 * 1024) throw new Error('图片超过大小限制');
      setUploading(true); void Taro.showLoading({ title: '图片上传中' }); const url = await uploadImage(filePath); if (sendPayload({ message_type: 'image', url })) setMoreVisible(false);
    } catch (error: any) { void Taro.showToast({ title: error?.msg || error?.message || '图片发送失败', icon: 'none' }); }
    finally { void Taro.hideLoading(); setUploading(false); }
  };
  const endConversation = async () => {
    if (!conversationNo || closed) return;
    const confirmation = await Taro.showModal({ title: '结束咨询', content: '确定结束本次客服会话吗？' }); if (!confirmation.confirm) return;
    try { await closeCustomerConversation(conversationNo); setConversation((current: any) => ({ ...current, receptionState: 'closed', status: 'closed' })); sessionRef.current?.stop(); setStatus('会话已结束'); setMoreVisible(false); }
    catch (error: any) { void Taro.showToast({ title: error?.msg || '结束会话失败', icon: 'none' }); }
  };
  const transferHuman = async () => {
    if (!conversationNo || transferring) return; setTransferring(true);
    try { await requestHuman(conversationNo); setConversation(await getCustomerConversation(conversationNo)); }
    catch (error: any) { void Taro.showToast({ title: error?.msg || '转人工失败，请重试', icon: 'none' }); } finally { setTransferring(false); }
  };
  const selectCategory = async (index: number) => {
    if (!conversationNo || !categories[index]) return;
    try { await classifyCustomerConversation(conversationNo, categories[index].id); setConversation(await getCustomerConversation(conversationNo)); }
    catch (error: any) { void Taro.showToast({ title: error?.msg || '选择类目失败', icon: 'none' }); }
  };
  const rate = async (score: number) => {
    try { await rateCustomerConversation(conversationNo, score); setRated(true); void Taro.showToast({ title: '感谢评价', icon: 'success' }); }
    catch { void Taro.showToast({ title: '评价失败，请重试', icon: 'none' }); }
  };
  return (
    <View className="customer-service">
      {isH5() && <PageNav title="联系客服" />}
      {!!connectionError && <View className="customer-service__error"><Text>{connectionError}</Text><Button size="mini" onClick={() => void connect()}>重新连接</Button></View>}
      <View className="customer-service__header">
        <View className="customer-service__agent">
          <View className="customer-service__agent-avatar"><Text>客</Text></View>
          <View>
            <Text className="customer-service__title">{reception === 'ai_active' ? 'AI 客服' : '在线客服'}</Text>
            <View className="customer-service__status"><Text className={`customer-service__status-dot ${status === '在线' ? 'is-online' : ''}`} /><Text>{statusLabel}</Text></View>
          </View>
        </View>
        {!!conversationNo && <View className="customer-service__end" onClick={closed ? connect : endConversation}><Text>{closed ? '重新咨询' : '结束咨询'}</Text></View>}
      </View>

      <View className="customer-service__routing">
        {categories.length > 0 && !closed && <Picker mode="selector" range={categories.map(c => categoryPath(categories, c))} onChange={event => void selectCategory(Number(event.detail.value))}><Text>问题类目：{categoryPath(categories, categories.find(c => c.id === (conversation?.categoryId || conversation?.category_id)) || { name: '请选择' })} ▾</Text></Picker>}
        {(reception === 'ai_active' || reception === 'human_deferred') && <Button size="mini" loading={transferring} onClick={transferHuman}>转人工客服</Button>}
        {['human_waiting', 'human_assigned', 'human_deferred'].includes(reception) && <Text>{receptionLabels[reception]}</Text>}
      </View>
      <ScrollView scrollY className="customer-service__messages" scrollIntoView={messages.length ? `support-message-${messages.length - 1}` : undefined}>
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
            <View id={`support-message-${index}`} key={message.id || message.clientMessageId || message.client_message_id || index} className={`customer-service__message ${mine ? 'is-mine' : ''}`}>
              {!mine && <View className="customer-service__message-avatar"><Text>{senderOf(message) === 'ai' ? 'AI' : '客'}</Text></View>}
              <View className="customer-service__message-body">
                {type === 'image' && content.url
                  ? <Image className="customer-service__message-image" src={content.url} mode="widthFix" onClick={() => Taro.previewImage({ current: content.url, urls: [content.url!] })} />
                  : type === 'product' || type === 'product_card'
                    ? <View className="customer-service__product" onClick={() => { const id = Number(content.product_id || content.productId); if (id > 0) void Taro.navigateTo({ url: `/pages/goods/details/index?id=${id}` }); }}>{content.image && <Image src={content.image} mode="aspectFill" />}<Text>{content.title || '商品咨询'}</Text><Text>查看商品 ›</Text></View>
                    : <View className="customer-service__bubble"><Text>{content.text || content.answer || content.title || '[暂不支持的消息]'}</Text></View>}
                {mine && message.delivery === 'sending' && <Text className="customer-service__delivery">发送中…</Text>}
                {mine && message.delivery === 'failed' && <Text className="customer-service__retry" onClick={() => sessionRef.current?.retry(clientIdOf(message) || '')}>发送未确认，点击重试</Text>}
                {formatTime(message) && <Text className="customer-service__message-time">{formatTime(message)}</Text>}
              </View>
              {mine && <View className="customer-service__message-avatar customer-service__message-avatar--mine"><Text>我</Text></View>}
            </View>
          );
        })}
      </ScrollView>

      {moreVisible && !closed && imageEnabled && !!conversationNo && (
        <View className="customer-service__more-panel">
          <View className="customer-service__more-action" onClick={() => void chooseAndSendImage(['album'])}>
            <View className="customer-service__more-icon"><Text>图</Text></View><Text>相册</Text>
          </View>
          <View className="customer-service__more-action" onClick={() => void chooseAndSendImage(['camera'])}>
            <View className="customer-service__more-icon"><Text>拍</Text></View><Text>拍照</Text>
          </View>
        </View>
      )}

      {closed && !rated && <View className="customer-service__rating"><Text>本次服务满意吗？</Text><Button size="mini" onClick={() => void rate(5)}>满意</Button><Button size="mini" onClick={() => void rate(1)}>不满意</Button></View>}
      {!closed && <View className="customer-service__composer">
        {imageEnabled && <View className={`customer-service__add ${moreVisible ? 'is-active' : ''}`} onClick={() => setMoreVisible((visible) => !visible)}><Text>＋</Text></View>}
        <Input className="customer-service__input" disabled={!conversationNo} value={text} maxlength={serviceConfig.maxMessageLength || serviceConfig.max_message_length || 1000} adjustPosition placeholder="输入消息…" onInput={(event) => setText(event.detail.value)} confirmType="send" onConfirm={sendText} />
        <Button className={`customer-service__send ${canSendText ? 'is-ready' : ''}`} disabled={!canSendText} onClick={sendText}>发送</Button>
      </View>}
    </View>
  );
}
