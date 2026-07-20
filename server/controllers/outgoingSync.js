import { dbQuery } from '../database/index.js';

const esc = value => String(value ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");

const messageTextFromPayload = payload => {
  if (typeof payload === 'string') return payload;
  if (!payload || typeof payload !== 'object') return '';
  if (payload.text) return payload.footer ? `${payload.text}\n\n> _${payload.footer}_` : payload.text;
  if (payload.caption) return payload.footer ? `${payload.caption}\n\n> _${payload.footer}_` : payload.caption;
  if (payload.message) return payload.message;
  return JSON.stringify(payload);
};

const normalizeMessageText = message => {
  if (typeof message !== 'string') return messageTextFromPayload(message);
  try {
    return messageTextFromPayload(JSON.parse(message));
  } catch {
    return message;
  }
};

const resolveDevice = async sender => {
  const devices = await dbQuery(
    "SELECT id, user_id FROM devices WHERE body = '" + esc(sender) + "' LIMIT 1"
  );
  return devices.length ? devices[0] : null;
};

const syncOutgoingMessage = async ({
  sender,
  receiver,
  message,
  type = 'text',
  status = 'success',
  note = null,
  sendBy = 'web',
  payload = '{}',
  messageId = null,
  insertHistory = false,
}) => {
  if (!sender || !receiver) return null;

  const device = await resolveDevice(sender);
  if (!device) return null;

  const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
  const body = esc(sender);
  const phone = esc(receiver);
  const text = esc(normalizeMessageText(message));
  const safeType = esc(type || 'text');
  const safeMessageId = messageId ? esc(messageId) : null;

  let sessions = await dbQuery(
    "SELECT id FROM chat_sessions WHERE body = '" + body + "' AND phone_number = '" + phone + "' LIMIT 1"
  );

  if (!sessions.length) {
    await dbQuery(
      "INSERT INTO chat_sessions (user_id, body, phone_number, push_name, cs_name, last_message, last_seen_at, created_at, updated_at) VALUES ('" +
        device.user_id + "', '" + body + "', '" + phone + "', '" + phone + "', '', '" + text + "', '" + now + "', '" + now + "', '" + now + "')"
    );
    sessions = await dbQuery(
      "SELECT id FROM chat_sessions WHERE body = '" + body + "' AND phone_number = '" + phone + "' LIMIT 1"
    );
  }

  let sessionId = null;
  if (sessions.length) {
    sessionId = sessions[0].id;
    const existing = safeMessageId
      ? await dbQuery("SELECT id FROM chat_messages WHERE wapp_id = '" + safeMessageId + "' LIMIT 1")
      : [];

    if (!existing.length) {
      await dbQuery(
        "INSERT INTO chat_messages (wapp_id, session_id, number, direction, message, type, push_name, attachment, original_file, created_at, updated_at) VALUES (" +
          (safeMessageId ? "'" + safeMessageId + "'" : 'NULL') + ", '" + sessionId + "', '" + phone + "', 'outgoing', '" + text + "', '" + safeType + "', '', '', '', '" + now + "', '" + now + "')"
      );
    }

    await dbQuery(
      "UPDATE chat_sessions SET last_message = '" + text + "', last_seen_at = '" + now + "', updated_at = '" + now + "' WHERE id = '" + sessionId + "'"
    );
  }

  if (insertHistory) {
    await dbQuery(
      "INSERT INTO message_histories (user_id, device_id, number, type, message, payload, status, send_by, note, created_at, updated_at) VALUES ('" +
        device.user_id + "', '" + device.id + "', '" + phone + "', '" + safeType + "', '" + text + "', '" + esc(payload) + "', '" + esc(status) + "', '" + esc(sendBy) + "', " +
        (note ? "'" + esc(note) + "'" : 'NULL') + ", '" + now + "', '" + now + "')"
    );
  }

  return { sessionId, userId: device.user_id, deviceId: device.id };
};

export { esc, normalizeMessageText, syncOutgoingMessage };
