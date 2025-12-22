# Socket.IO 客户端集成示例

## 前端 JavaScript 客户端

### 安装 Socket.IO 客户端

```bash
npm install socket.io-client
```

### 基本连接示例

```javascript
import { io } from 'socket.io-client';

// 连接到Socket.IO服务器
const socket = io('http://localhost:9502/system-message', {
  query: {
    token: 'your-jwt-token-here' // 用户认证token
  },
  transports: ['websocket', 'polling']
});

// 连接成功
socket.on('connected', (data) => {
  console.log('Connected to system message service:', data);
});

// 接收消息通知
socket.on('message_notification', (data) => {
  console.log('New message notification:', data);
  
  // 显示通知
  showNotification(data.title, data.content, data.priority);
  
  // 更新未读消息数量
  updateUnreadCount();
});

// 接收广播消息
socket.on('message_broadcast', (data) => {
  console.log('Broadcast message:', data);
  showBroadcastMessage(data);
});

// 接收消息状态更新
socket.on('message_status_update', (data) => {
  console.log('Message status update:', data);
  updateMessageStatus(data.message_id, data.status);
});

// 接收未读数量更新
socket.on('unread_count_update', (data) => {
  console.log('Unread count update:', data);
  updateUnreadBadge(data.count);
});

// 连接错误
socket.on('connect_error', (error) => {
  console.error('Connection error:', error);
});

// 断开连接
socket.on('disconnect', (reason) => {
  console.log('Disconnected:', reason);
});
```

### Vue.js 集成示例

```javascript
// plugins/socketio.js
import { io } from 'socket.io-client';

class SocketIOManager {
  constructor() {
    this.socket = null;
    this.connected = false;
  }

  connect(token) {
    if (this.socket) {
      this.disconnect();
    }

    this.socket = io('http://localhost:9502/system-message', {
      query: { token },
      transports: ['websocket', 'polling']
    });

    this.setupEventHandlers();
  }

  setupEventHandlers() {
    this.socket.on('connected', (data) => {
      this.connected = true;
      console.log('Socket.IO connected:', data);
    });

    this.socket.on('message_notification', (data) => {
      // 触发Vue事件总线或使用Vuex
      this.$eventBus.$emit('message-notification', data);
    });

    this.socket.on('disconnect', () => {
      this.connected = false;
    });
  }

  disconnect() {
    if (this.socket) {
      this.socket.disconnect();
      this.socket = null;
      this.connected = false;
    }
  }

  isConnected() {
    return this.connected;
  }
}

export default new SocketIOManager();
```

### React Hook 示例

```javascript
// hooks/useSocketIO.js
import { useEffect, useRef, useState } from 'react';
import { io } from 'socket.io-client';

export const useSocketIO = (token) => {
  const socketRef = useRef(null);
  const [connected, setConnected] = useState(false);
  const [notifications, setNotifications] = useState([]);

  useEffect(() => {
    if (!token) return;

    // 连接Socket.IO
    socketRef.current = io('http://localhost:9502/system-message', {
      query: { token },
      transports: ['websocket', 'polling']
    });

    const socket = socketRef.current;

    // 事件处理
    socket.on('connected', () => {
      setConnected(true);
    });

    socket.on('message_notification', (data) => {
      setNotifications(prev => [...prev, data]);
    });

    socket.on('disconnect', () => {
      setConnected(false);
    });

    // 清理函数
    return () => {
      socket.disconnect();
    };
  }, [token]);

  return {
    connected,
    notifications,
    socket: socketRef.current
  };
};
```

## 服务端事件类型

### 客户端接收的事件

| 事件名 | 描述 | 数据格式 |
|--------|------|----------|
| `connected` | 连接成功 | `{message, user_id, socket_id}` |
| `message_notification` | 新消息通知 | `{type, message_id, title, content, message_type, priority, created_at}` |
| `message_broadcast` | 广播消息 | `{type, title, content, timestamp}` |
| `message_status_update` | 消息状态更新 | `{type, message_id, status, timestamp}` |
| `unread_count_update` | 未读数量更新 | `{type, count, timestamp}` |

### 客户端发送的事件

客户端通常不需要主动发送事件，主要是接收服务端推送的消息。如果需要发送事件，可以添加：

```javascript
// 标记消息为已读（可选，也可以通过HTTP API）
socket.emit('mark_as_read', { message_id: 123 });

// 加入特定房间（如果需要）
socket.emit('join_room', { room: 'admin' });
```

## 错误处理

```javascript
socket.on('connect_error', (error) => {
  console.error('Socket.IO connection error:', error);
  
  // 可以实现重连逻辑
  setTimeout(() => {
    socket.connect();
  }, 5000);
});

socket.on('error', (error) => {
  console.error('Socket.IO error:', error);
});
```

## 认证和安全

```javascript
// 在连接时传递JWT token
const socket = io('http://localhost:9502/system-message', {
  query: {
    token: localStorage.getItem('jwt_token')
  },
  // 其他配置...
});

// 如果token过期，重新连接
socket.on('auth_error', () => {
  // 刷新token
  const newToken = refreshToken();
  socket.disconnect();
  socket.query.token = newToken;
  socket.connect();
});
```

## 配置选项

```javascript
const socket = io('http://localhost:9502/system-message', {
  // 认证
  query: {
    token: 'jwt-token'
  },
  
  // 传输方式
  transports: ['websocket', 'polling'],
  
  // 重连配置
  reconnection: true,
  reconnectionAttempts: 5,
  reconnectionDelay: 1000,
  
  // 超时配置
  timeout: 20000,
  
  // CORS
  withCredentials: true
});
```

这个示例展示了如何在前端应用中集成Socket.IO客户端来接收系统消息通知。