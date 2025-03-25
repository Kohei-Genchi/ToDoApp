declare global {
  interface Window {
    webkitAudioContext: typeof AudioContext
  }
}

export interface AudioCompressionOptions {
  threshold?: number
  knee?: number
  ratio?: number
  attack?: number
  release?: number
  sampleRate?: number
}

export async function compressAudio(
  audioBlob: Blob,
  options: AudioCompressionOptions = {}
): Promise<Blob> {
  const {
    threshold = -50,
    knee = 40,
    ratio = 12,
    attack = 0,
    release = 0.25,
    sampleRate = 22050
  } = options

  try {
    const arrayBuffer = await audioBlob.arrayBuffer()
    const AudioContext = window.AudioContext || window.webkitAudioContext
    const audioContext = new AudioContext()
    const audioBuffer = await audioContext.decodeAudioData(arrayBuffer)

    const offlineContext = new OfflineAudioContext(
      1,
      audioBuffer.length,
      sampleRate
    )

    const source = offlineContext.createBufferSource()
    source.buffer = audioBuffer

    const compressor = offlineContext.createDynamicsCompressor()
    compressor.threshold.value = threshold
    compressor.knee.value = knee
    compressor.ratio.value = ratio
    compressor.attack.value = attack
    compressor.release.value = release

    source.connect(compressor)
    compressor.connect(offlineContext.destination)

    source.start(0)
    const renderedBuffer = await offlineContext.startRendering()

    return bufferToWav(renderedBuffer)
  } catch (error) {
    console.error('Audio compression failed, using original', error)
    return audioBlob
  }
}

export function bufferToWav(buffer: AudioBuffer): Blob {
  const numOfChannels = buffer.numberOfChannels
  const length = buffer.length * numOfChannels * 2
  const sampleRate = buffer.sampleRate

  const arrayBuffer = new ArrayBuffer(44 + length)
  const view = new DataView(arrayBuffer)

  const writeString = (offset: number, str: string) => {
    for (let i = 0; i < str.length; i++) {
      view.setUint8(offset + i, str.charCodeAt(i))
    }
  }

  // RIFF header
  writeString(0, 'RIFF')
  view.setUint32(4, 36 + length, true)
  writeString(8, 'WAVE')

  // fmt subchunk
  writeString(12, 'fmt ')
  view.setUint32(16, 16, true)
  view.setUint16(20, 1, true)
  view.setUint16(22, numOfChannels, true)
  view.setUint32(24, sampleRate, true)
  view.setUint32(28, sampleRate * numOfChannels * 2, true)
  view.setUint16(32, numOfChannels * 2, true)
  view.setUint16(34, 16, true)

  // data subchunk
  writeString(36, 'data')
  view.setUint32(40, length, true)

  let offset = 44
  for (let i = 0; i < buffer.length; i++) {
    for (let channel = 0; channel < numOfChannels; channel++) {
      const sample = Math.max(-1, Math.min(1, buffer.getChannelData(channel)[i]))
      const val = sample < 0 ? sample * 0x8000 : sample * 0x7fff
      view.setInt16(offset, val, true)
      offset += 2
    }
  }

  return new Blob([view], { type: 'audio/wav' })
}
