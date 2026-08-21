
import languages from '@/lib/languages'

  const dictionaries = {
    'vn': languages.vi,
  }

export const getDictionary = (country) => {
  return dictionaries[country] ?? languages.en
}
