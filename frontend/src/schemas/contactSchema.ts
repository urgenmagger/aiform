import { z } from 'zod';

export const contactSchema = z.object({
  name: z
    .string()
    .trim()
    .min(2, 'Имя должно быть не короче 2 символов')
    .max(100, 'Имя должно быть не длиннее 100 символов'),
  phone: z
    .string()
    .trim()
    .min(1, 'Телефон обязателен')
    .max(30, 'Телефон должен быть не длиннее 30 символов')
    .regex(/^[\d+\-()\s]+$/, 'Телефон может содержать только цифры, +, -, (, ) и пробелы'),
  email: z
    .string()
    .trim()
    .email('Введите корректный email')
    .max(150, 'Email должен быть не длиннее 150 символов'),
  comment: z
    .string()
    .trim()
    .min(10, 'Комментарий должен быть не короче 10 символов')
    .max(2000, 'Комментарий должен быть не длиннее 2000 символов'),
});

export type ContactPayload = z.infer<typeof contactSchema>;
