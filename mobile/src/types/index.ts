export interface User {
  id: number;
  username: string;
  roles: string[];
}

export interface League {
  id: number;
  name: string;
}

export interface AuthResponse {
  apiToken: string;
  user: User;
  league: League;
}

export interface Player {
  id: number;
  firstname: string;
  lastname: string;
  isDefunct: boolean;
  seedHandicapIndex: number;
}

export interface PlayerDetail extends Player {
  middlenameOrInitial?: string;
  generation?: string;
  email?: string;
  phone?: string;
  address?: {
    line1?: string;
    line2?: string;
    city?: string;
    postalCode?: string;
    region?: string;
  };
}

export interface Event {
  id: number;
  eventNumber: number;
  startDateTime: string;
  description: string;
  format: string;
}

export interface SessionWithEvents {
  id: number;
  name: string;
  events: Event[];
}

export interface EventDetail extends Event {
  course?: string;
  nine?: string;
  isWithHandicapping: boolean;
  isRegistered: boolean;
}

export interface Game {
  id: number;
  startingTime: string;
  isRecorded: boolean;
  players: {
    id: number;
    name: string;
  }[];
}

export interface HoleInfo {
  number: number;
  par: number;
  handicap: number;
}

export interface NineInfo {
  id: number;
  name: string;
  holes: HoleInfo[];
}

export interface TeeInfo {
  id: number;
  name: string;
}

export interface PlayerScore {
  playerId: number;
  playerName: string;
  isPlayed: boolean;
  currentTeeId: number;
  availableTees: TeeInfo[];
  strokes: (number | null)[];
}

export interface ScoreEntryDetails {
  gameId: number;
  isRecorded: boolean;
  nines: NineInfo[];
  playerScores: PlayerScore[];
}

export interface Season {
  id: number;
  name: string;
  startDate: string;
  endDate: string;
}

export interface SeasonDetail extends Season {
  sessions: {
    id: number;
    name: string;
    startDate: string;
  }[];
}
