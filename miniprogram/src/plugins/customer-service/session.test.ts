import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { CustomerServiceSession, mergeMessages } from './session';
import type { SessionDependencies, SupportMessage, SupportSocket } from './session';
class FakeSocket implements SupportSocket {
  send = vi.fn(); close = vi.fn(); opened = () => {}; received = (_: { data: string }) => {}; closed = () => {};
  onOpen(callback: () => void) { this.opened = callback; }
  onMessage(callback: (event: { data: string }) => void) { this.received = callback; }
  onClose(callback: () => void) { this.closed = callback; }
  emit(event: string, payload = {}, context = {}) { this.received({ data: JSON.stringify({ event, payload, ...context }) }); }
}
function fixture(overrides: Partial<SessionDependencies> = {}) {
  const socket = new FakeSocket(); let messages: SupportMessage[] = [];
  const deps: SessionDependencies = { ticket: vi.fn().mockResolvedValue({ ticket: 'single-use' }), socket: vi.fn().mockResolvedValue(socket), history: vi.fn().mockResolvedValue([]), conversation: vi.fn().mockResolvedValue({ receptionState: 'ai_active' }), sendText: vi.fn().mockResolvedValue({ id: 1, sequence: 1, clientMessageId: 'same-id', contentJson: { text: '问题' }, senderType: 'member' }), messages: vi.fn(items => { messages = mergeMessages(messages, items); }), status: vi.fn(), updateConversation: vi.fn(), error: vi.fn(), ...overrides };
  const session = new CustomerServiceSession('C1', deps); session.start(); return { session, socket, deps, messages: () => messages };
}
beforeEach(() => vi.useFakeTimers());
afterEach(() => { vi.clearAllTimers(); vi.useRealTimers(); });
describe('客服持久会话', () => {
  it('认证确认后才在线，每25秒心跳；未认证不发送图片', async () => {
    const f = fixture(); await vi.advanceTimersByTimeAsync(0); f.socket.opened(); expect(f.deps.status).not.toHaveBeenCalledWith('在线');
    await f.session.send({ message_type: 'image', url: 'https://img.example/a.jpg' }, 'image-id'); expect(f.messages()[0].delivery).toBe('failed');
    expect(f.socket.send.mock.calls).toHaveLength(1); expect(JSON.parse(f.socket.send.mock.calls[0][0].data).event).toBe('auth');
    f.socket.emit('authenticated'); await vi.advanceTimersByTimeAsync(25000); expect(f.deps.status).toHaveBeenCalledWith('在线'); expect(f.socket.send.mock.calls.some(([m]) => JSON.parse(m.data).event === 'ping')).toBe(true);
    f.session.stop(); const count = f.socket.send.mock.calls.length; await vi.advanceTimersByTimeAsync(60000); expect(f.socket.send).toHaveBeenCalledTimes(count);
  });
  it('205条消息分页补拉；乱序socket不推进持久游标；重复消息合并', async () => {
    const history = vi.fn(async (after: number) => Array.from({ length: Math.min(100, 205 - after) }, (_, i) => ({ id: after + i + 1, sequence: after + i + 1 })));
    const f = fixture({ history }); await vi.advanceTimersByTimeAsync(0); f.socket.opened(); f.socket.emit('authenticated'); f.socket.emit('message:created', { id: 205, sequence: 205 }, { conversation_no: 'C1' }); await f.session.recover();
    expect(history.mock.calls.map(call => call[0])).toEqual([0, 100, 200]); expect(f.messages()).toHaveLength(205); expect(f.messages().map(m => m.sequence)).toEqual(Array.from({ length: 205 }, (_, i) => i + 1)); f.session.stop();
  });
  it('失败重试保持幂等ID，ACK不能确认送达，持久消息替换待发送', async () => {
    const sendText = vi.fn().mockRejectedValueOnce(new Error('network')).mockResolvedValueOnce({ id: 1, sequence: 1, client_message_id: 'stable', sender_type: 'member', content_json: { text: '退货' } });
    const f = fixture({ sendText }); await vi.advanceTimersByTimeAsync(0); f.socket.opened(); f.socket.emit('authenticated'); await f.session.send({ message_type: 'text', text: '退货' }, 'stable'); expect(f.messages()[0].delivery).toBe('failed');
    f.socket.emit('ack', { client_message_id: 'stable' }); expect(f.messages()[0].delivery).toBe('failed'); f.session.retry('stable'); await vi.advanceTimersByTimeAsync(0);
    expect(sendText.mock.calls).toEqual([['stable', '退货'], ['stable', '退货']]); expect(f.messages()).toHaveLength(1); expect(f.messages()[0].delivery).toBe('sent');
    f.socket.emit('message:created', { id: 1, sequence: 1, client_message_id: 'stable' }, { conversation_no: 'C1' }); expect(f.messages()).toHaveLength(1); f.session.stop();
  });
  it('重连获取新票据并重新认证，隐藏页面停止重连', async () => {
    const f = fixture(); await vi.advanceTimersByTimeAsync(0); f.socket.opened(); f.socket.emit('authenticated'); f.socket.closed(); await vi.advanceTimersByTimeAsync(2000); expect(f.deps.ticket).toHaveBeenCalledTimes(2); expect(f.deps.status).toHaveBeenLastCalledWith('正在连接'); f.session.stop(); f.socket.closed(); await vi.advanceTimersByTimeAsync(5000); expect(f.deps.ticket).toHaveBeenCalledTimes(2);
  });
  it('忽略其他会话事件，HTTP文本可在socket重连时确认', async () => {
    const f = fixture(); await vi.advanceTimersByTimeAsync(0); f.socket.opened(); f.socket.emit('authenticated'); f.socket.emit('message:created', { id: 99 }, { conversation_no: 'OTHER' }); expect(f.messages()).toHaveLength(0); f.socket.closed(); await f.session.send({ message_type: 'text', text: '问题' }, 'same-id'); expect(f.messages()[0].delivery).toBe('sent'); f.session.stop();
  });
});
