import axios from 'axios'
import type { AxiosInstance } from 'axios'

export interface AITask {
  title: string
  description?: string
  dueDate?: Date
}

export interface AIResponse {
  success: boolean
  tasks?: AITask[]
  text?: string
  message?: string
}

export class AIService {
  private client: AxiosInstance

  constructor(csrfToken: string) {
    this.client = axios.create({
      baseURL: '/api',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      withCredentials: true
    })
  }

  async processAudio(audioBlob: Blob): Promise<AIResponse> {
    const formData = new FormData()
    formData.append('audio', audioBlob, 'recording.webm')

    try {
      const response = await this.client.post<AIResponse>(
        '/speech-to-tasks',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        }
      )
      return response.data
    } catch (error) {
      return this.handleError(error)
    }
  }

  async splitTasks(text: string): Promise<AIResponse> {
    try {
      const response = await this.client.post<AIResponse>('/split-tasks', {
        text
      })
      return response.data
    } catch (error) {
      return this.handleError(error)
    }
  }

  private handleError(error: unknown): AIResponse {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'API request failed'
      }
    }
    return {
      success: false,
      message: 'Unknown error occurred'
    }
  }
}