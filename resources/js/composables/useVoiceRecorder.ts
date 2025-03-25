import { ref } from 'vue'
import type { Ref } from 'vue'
import { compressAudio, bufferToWav } from '@/utils/audio'

export interface VoiceRecorder {
  isRecording: Ref<boolean>
  recordingTime: Ref<number>
  startRecording: () => Promise<void>
  stopRecording: () => Promise<void>
  getAudioBlob: () => Blob | null
  error: Ref<string | null>
}

export function useVoiceRecorder(): VoiceRecorder {
  const isRecording = ref(false)
  const recordingTime = ref(0)
  const mediaRecorder = ref<MediaRecorder | null>(null)
  const audioChunks = ref<Blob[]>([])
  const error = ref<string | null>(null)
  let recordingInterval: number | null = null
  let stream: MediaStream | null = null

  const startRecording = async () => {
    try {
      error.value = null
      stream = await navigator.mediaDevices.getUserMedia({ audio: true })

      mediaRecorder.value = new MediaRecorder(stream)
      audioChunks.value = []

      mediaRecorder.value.ondataavailable = (event) => {
        if (event.data.size > 0) {
          audioChunks.value.push(event.data)
        }
      }

      mediaRecorder.value.start()
      isRecording.value = true
      recordingTime.value = 0

      recordingInterval = window.setInterval(() => {
        recordingTime.value++
        if (recordingTime.value >= 30) {
          stopRecording()
        }
      }, 1000)

    } catch (err) {
      error.value = 'マイクへのアクセスができませんでした。ブラウザの設定を確認してください。'
      isRecording.value = false
    }
  }

  const stopRecording = async (): Promise<void> => {
    return new Promise((resolve) => {
      if (!mediaRecorder.value || mediaRecorder.value.state === 'inactive') {
        resolve()
        return
      }

      mediaRecorder.value.onstop = () => {
        if (stream) {
          stream.getTracks().forEach(track => track.stop())
          stream = null
        }
        resolve()
      }

      mediaRecorder.value.stop()
      isRecording.value = false

      if (recordingInterval) {
        clearInterval(recordingInterval)
        recordingInterval = null
      }
    })
  }

  const getAudioBlob = () => {
    if (audioChunks.value.length === 0) return null
    return new Blob(audioChunks.value, { type: 'audio/webm' })
  }

  return {
    isRecording,
    recordingTime,
    startRecording,
    stopRecording,
    getAudioBlob,
    error
  }
}