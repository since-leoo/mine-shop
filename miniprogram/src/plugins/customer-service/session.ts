export type SupportMessage = { id?: number; sequence?: number; client_message_id?: string; clientMessageId?: string; message_type?: string; messageType?: string; content_json?: Record<string, any>; contentJson?: Record<string, any>; sender_type?: string; senderType?: string; sent_at?: string; sentAt?: string; delivery?: 'sending' | 'sent' | 'failed' };
export type SupportSocket = { send: (options: { data: string; fail?: () => void }) => void; close: (options: object) => void; onOpen: (callback: () => void) => void; onMessage: (callback: (event: { data: string }) => void) => void; onClose: (callback: () => void) => void; onError?: (callback: () => void) => void };
export function clientIdOf(m: SupportMessage) { return m.client_message_id || m.clientMessageId; }
export function mergeMessages(previous: SupportMessage[], incoming: SupportMessage[]): SupportMessage[] {
  const result = [...previous];
  for (const message of incoming) {
    const index = result.findIndex(item => (message.id && item.id === message.id) || (clientIdOf(message) && clientIdOf(item) === clientIdOf(message)));
    const next = { ...message, delivery: message.id ? 'sent' as const : message.delivery };
    if (index < 0) result.push(next); else result[index] = { ...result[index], ...next };
  }
  return result.sort((a, b) => (a.sequence || Number.MAX_SAFE_INTEGER) - (b.sequence || Number.MAX_SAFE_INTEGER) || (a.id || 0) - (b.id || 0));
}
export interface SessionDependencies {
  ticket: () => Promise<any>;
  socket: () => Promise<SupportSocket>;
  history: (after: number, limit: number) => Promise<SupportMessage[]>;
  conversation: () => Promise<any>;
  sendText: (id: string, text: string) => Promise<SupportMessage>;
  messages: (messages: SupportMessage[]) => void;
  status: (status: string) => void;
  updateConversation: (conversation: any) => void;
  error: (message: string) => void;
}
/** Authentication, presence and durable recovery are independent from the page lifecycle. */
export class CustomerServiceSession {
  private socket?: SupportSocket;
  private stopped = true;
  private authenticated = false;
  private generation = 0;
  private sequence = 0;
  private reconnectTimer?: ReturnType<typeof setTimeout>;
  private heartbeat?: ReturnType<typeof setInterval>;
  private recoveryTimer?: ReturnType<typeof setInterval>;
  private authTimer?: ReturnType<typeof setTimeout>;
  private recovering?: Promise<void>;
  private pending = new Map<string, { payload: Record<string, any>; timer?: ReturnType<typeof setTimeout> }>();
  private lastPong = 0;
  constructor(private readonly no: string, private readonly deps: SessionDependencies) {}
  start() { this.stopped = false; void this.connect(); this.recoveryTimer = setInterval(() => { void this.recover().catch(() => undefined); }, 15000); }
  stop() { this.stopped = true; this.generation++; this.authenticated = false; this.clearConnectionTimers(); clearTimeout(this.reconnectTimer); clearInterval(this.recoveryTimer); this.socket?.close({}); this.socket = undefined; for (const [id, pending] of this.pending) { clearTimeout(pending.timer); this.deps.messages([{ client_message_id: id, delivery: 'failed' }]); } }
  private clearConnectionTimers() { clearInterval(this.heartbeat); clearTimeout(this.authTimer); }
  private retryConnection() {
    if (this.stopped) return;
    this.authenticated = false; this.clearConnectionTimers(); this.deps.status('连接已断开，正在重连');
    clearTimeout(this.reconnectTimer); this.reconnectTimer = setTimeout(() => { void this.connect(); }, 2000);
  }
  private async connect() {
    const generation = ++this.generation;
    this.authenticated = false; this.clearConnectionTimers(); this.deps.status('正在连接');
    this.socket?.close({});
    try {
      const ticket = await this.deps.ticket();
      if (this.stopped || generation !== this.generation) return;
      const socket = await this.deps.socket();
      if (this.stopped || generation !== this.generation) { socket.close({}); return; }
      this.socket = socket;
      const current = () => !this.stopped && generation === this.generation;
      socket.onOpen(() => {
        if (!current()) return;
        this.deps.status('正在认证'); socket.send({ data: JSON.stringify({ event: 'auth', payload: { ticket: ticket.ticket || ticket } }) });
        this.authTimer = setTimeout(() => { if (!this.authenticated && current()) { socket.close({}); this.retryConnection(); } }, 10000);
      });
      socket.onMessage(event => {
        if (!current()) return;
        let frame: any; try { frame = JSON.parse(event.data); } catch { return; }
        if (frame.event === 'authenticated') {
          if (this.authenticated) return;
          this.authenticated = true; clearTimeout(this.authTimer); this.lastPong = Date.now(); this.deps.status('在线');
          this.heartbeat = setInterval(() => { if (!current()) return; if (Date.now() - this.lastPong > 60000) { socket.close({}); this.retryConnection(); return; } socket.send({ data: JSON.stringify({ event: 'ping', payload: {} }) }); }, 25000);
          void this.recover().catch(() => undefined); return;
        }
        if (frame.event === 'pong') { this.lastPong = Date.now(); return; }
        if (!this.authenticated) return;
        const no = frame.conversation_no || frame.payload?.conversation_no || frame.payload?.conversationNo;
        if (no && no !== this.no) return;
        if (frame.event === 'message:created') this.delivered(frame.payload || {});
        if (frame.event?.startsWith('conversation:')) void this.refreshConversation();
        if (frame.event === 'system:error') {
          const id = frame.client_message_id; if (id) this.failed(id);
          this.deps.error(frame.payload?.message || '消息发送失败');
        }
      });
      socket.onClose(() => { if (current()) this.retryConnection(); });
      socket.onError?.(() => { if (current()) { socket.close({}); this.retryConnection(); } });
    }
    catch { if (generation === this.generation) this.retryConnection(); }
  }
  private async refreshConversation() { try { const value = await this.deps.conversation(); if (!this.stopped) this.deps.updateConversation(value); } catch {} }
  async recover() {
    if (this.recovering) return this.recovering;
    this.recovering = (async () => {
      // Only advance the durable cursor through ordered HTTP pages. A later socket event can arrive before a missing earlier event.
      while (!this.stopped) {
        const page = await this.deps.history(this.sequence, 100); if (this.stopped) return;
        for (const message of page || []) this.delivered(message);
        const next = Math.max(this.sequence, ...(page || []).map(m => Number(m.sequence || 0)));
        if (next <= this.sequence || !page || page.length < 100) { this.sequence = next; break; }
        this.sequence = next;
      }
      await this.refreshConversation();
    })().finally(() => { this.recovering = undefined; });
    return this.recovering;
  }
  private delivered(message: SupportMessage) { const id = clientIdOf(message); if (id) { clearTimeout(this.pending.get(id)?.timer); this.pending.delete(id); } this.deps.messages([{ ...message, delivery: 'sent' }]); }
  private failed(id: string) { clearTimeout(this.pending.get(id)?.timer); this.deps.messages([{ client_message_id: id, delivery: 'failed' }]); }
  async send(payload: Record<string, any>, id: string): Promise<void> {
    if (this.stopped) return;
    const content = payload.message_type === 'image' ? { url: payload.url } : payload.message_type === 'product' ? { title: '商品咨询', product_id: payload.product_id } : { text: payload.text };
    this.deps.messages([{ client_message_id: id, sender_type: 'member', message_type: payload.message_type, content_json: content, sent_at: new Date().toISOString(), delivery: 'sending' }]);
    clearTimeout(this.pending.get(id)?.timer);
    this.pending.set(id, { payload, timer: setTimeout(() => this.failed(id), 12000) });
    try {
      // HTTP response confirms persistence and gives a useful fallback during socket reconnects.
      if (payload.message_type === 'text') this.delivered(await this.deps.sendText(id, payload.text));
      else if (this.authenticated && this.socket) this.socket.send({ data: JSON.stringify({ event: 'message:send', payload: { ...payload, conversation_no: this.no, client_message_id: id } }), fail: () => this.failed(id) });
      else this.failed(id);
    }
    catch { this.failed(id); }
  }
  retry(id: string) { const pending = this.pending.get(id); if (pending) void this.send(pending.payload, id); }
}
