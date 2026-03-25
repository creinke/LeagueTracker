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
    type?: string;
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
    type?: 'REGULAR' | 'TEAM';
    startingTime: string;
    isRecorded: boolean;
    players: {
        id: number;
        name: string;
        isTeam?: boolean;
        teamNumber?: number;
    }[];
    teamNames?: (string | null)[];
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
    matchId?: number | null;
    isPlayed: boolean;
    isDuplicate?: boolean;
    currentTeeId: number;
    availableTees: TeeInfo[];
    strokes: (number | null)[];
}

export interface TeamGroup {
    name: string | null;
    players: PlayerScore[];
    teamScore?: (number | null)[];
}

export interface ScoreEntryDetails {
    gameId: number;
    isRecorded: boolean;
    type: 'REGULAR' | 'TEAM';
    isScramble: boolean;
    nines: NineInfo[];
    playerScores?: PlayerScore[]; // For REGULAR
    teamOne?: TeamGroup;          // For TEAM
    teamTwo?: TeamGroup;          // For TEAM
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

export interface PlayerResult {
    id: number;
    name: string;
    place?: number | null;
    totalScore?: number;
    totalNetScore?: number;
    netScore?: number[];
    score?: number[];
    skins?: string | null;
    matchPoints?: number;
    sessionPoints?: number;
    seasonPoints?: number;
}

export interface PlayerDetailResult {
    name: string;
    handicap: number;
    firstNineScores: number[];
    firstNineNetScores: number[];
    firstNineTotalScore: number;
    firstNineTotalNetScore: number;
    secondNineScores: number[];
    secondNineNetScores: number[];
    secondNineTotalScore: number;
    secondNineTotalNetScore: number;
}

export interface TeamResult {
    id: number;
    name: string;
    teamName: string;
    players: string[] | PlayerDetailResult[];
    playerNames?: string[];
    gross: number;
    net: number;
    place: number;
    totalScore: number;
    totalNetScore: number;
    tieBreaker: string;
    firstNineScores: number[];
    firstNineNetScores: number[];
    firstNineTotalTeamScore: number;
    firstNineTotalTeamNetScore: number;
    firstNineTotalScore: number; // For Better Ball/Standard
    firstNineTotalNetScore: number; // For Better Ball/Standard
    secondNineScores: (number | string)[];
    secondNineNetScores: (number | string)[];
    secondNineTotalTeamScore: number;
    secondNineTotalTeamNetScore: number;
    secondNineTotalScore: number;
    secondNineTotalNetScore: number;
    handicap?: number; // For Scramble
}

export interface PlayerMatchResult {
    playerName: string;
    nineName: string;
    handicap: number;
    holeStrokes: number[];
    adjustedNetStrokes: number[];
    holePoints: number[];
    holeStrokesTotal: number;
    adjustedHoleStrokesTotal: number;
    netStrokesTotal: number;
    totalHolePoints: number;
    netPoints: number;
    totalPoints: number;
}

export interface TeamMatchResult {
    teamOneName: string;
    teamOnePlayerPoints: number;
    teamOneNetPoints: number;
    teamOneTotalPoints: number;
    teamTwoName: string;
    teamTwoPlayerPoints: number;
    teamTwoNetPoints: number;
    teamTwoTotalPoints: number;
}

export interface StandingResult {
    teamName: string;
    points: number;
    totalPoints: number;
    pointsBehind: number;
    games?: {
        opponent: string;
        points: number;
        opponentPoints: number;
        result: string;
    }[];
}

export interface MatchupResult {
    teamOne: string;
    teamOnePoints: number;
    teamTwo: string;
    teamTwoPoints: number;
}

export interface EventResults {
    eventId: number;
    description: string;
    format: string;
    resultType: 'SINGLES_STROKE' | 'SINGLES_MATCH' | 'TEAM_EVENT' | 'TEAM_STANDINGS';
    displayNet?: boolean;
    displayTotal?: boolean;
    scramble?: boolean;
    isLowTeamNet?: boolean;
    withHandicapping?: boolean;
    firstNineName?: string | null;
    secondNineName?: string | null;
    players?: PlayerResult[];
    teams?: TeamResult[];
    teamResults?: TeamMatchResult[];
    teamMatches?: PlayerMatchResult[][][];
    ninesPlayed?: number | { name: string }[];
    standings?: StandingResult[];
    matchups?: MatchupResult[];
}
