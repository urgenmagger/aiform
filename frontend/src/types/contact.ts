export type { ContactPayload } from '../schemas/contactSchema';

export interface AiAnalysis {
  category: string;
  sentiment: string;
  priority: string;
  summary: string;
  ai_available: boolean;
}

export interface ContactResponse {
  success: boolean;
  message: string;
  id: number;
  ai_analysis: AiAnalysis;
  mail_sent: boolean;
}
